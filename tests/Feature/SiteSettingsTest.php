<?php

use App\Models\User;
use App\Support\ContactSettings;
use App\Support\SeoSettings;
use App\Support\TurnstileSettings;

it('shows every settings section on one page', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Maklumat perhubungan')
        ->assertSee('SEO dan pratonton pautan')
        ->assertSee('Cloudflare Turnstile')
        ->assertSee('Gambar');
});

it('lets an admin publish the contact details shown in the footer', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.contact'), [
            'phone' => '03-1234 5678',
            'whatsapp' => '60123456789',
            'email' => 'hello@neekah.my',
            'address' => 'No. 1, Jalan Contoh, 50000 Kuala Lumpur',
            'hours' => 'Isnin – Jumaat, 9 pagi – 6 petang',
            'instagram' => 'https://instagram.com/neekahmy',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(app(ContactSettings::class)->whatsappUrl())->toBe('https://wa.me/60123456789');

    $this->get(route('vendors.index'))
        ->assertSee('03-1234 5678')
        ->assertSee('hello@neekah.my')
        ->assertSee('https://instagram.com/neekahmy');
});

it('rejects a social link that is not a URL', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.contact'), ['facebook' => 'neekahmy'])
        ->assertSessionHasErrors('facebook');
});

it('lets an admin rewrite the tagline and default description', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.seo'), [
            'tagline' => 'Majlis impian tanpa kerja gila',
            'description' => 'Tempah vendor perkahwinan yang disahkan di seluruh Malaysia, uruskan bajet dan checklist dalam satu tempat.',
            'twitter' => '@neekahmy',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect(app(SeoSettings::class)->twitter())->toBe('@neekahmy');

    expect(app(SeoSettings::class)->description())->toStartWith('Tempah vendor perkahwinan yang disahkan');

    // The footer prints the tagline on every public page.
    $this->get(route('landing'))->assertSee('Majlis impian tanpa kerja gila', false);
});

it('keeps the saved Turnstile secret when the field is left blank', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.turnstile'), ['enabled' => '1', 'site_key' => 'site-key', 'secret_key' => 'secret-key'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->put(route('admin.settings.turnstile'), ['enabled' => '1', 'site_key' => 'site-key-2', 'secret_key' => ''])
        ->assertSessionHasNoErrors();

    $turnstile = app(TurnstileSettings::class);
    expect($turnstile->secretKey())->toBe('secret-key')
        ->and($turnstile->siteKey())->toBe('site-key-2')
        ->and($turnstile->isEnabled())->toBeTrue();
});

it('keeps settings away from anyone who is not an admin', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('admin.settings.contact'), ['phone' => '03-1234 5678'])
        ->assertForbidden();
});
