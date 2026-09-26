<?php

use App\Http\Controllers\Admin\SettingController;
use App\Models\User;
use App\Support\ContactSettings;
use App\Support\SeoSettings;
use App\Support\TelegramSettings;
use App\Support\TurnstileSettings;

it('gives every settings group its own page, listed in one menu', function () {
    $admin = User::factory()->admin()->create();
    $ids = ['perhubungan', 'seo', 'gambar', 'keselamatan', 'telegram', 'pro', 'boost', 'tempahan', 'kamera', 'bayaran'];

    $response = $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk();

    expect($response->viewData('menu')->pluck('items')->flatten(1)->pluck('id')->all())->toBe($ids)
        // With no group named, the first one opens, and only it.
        ->and(collect($response->viewData('props')['sections'])->pluck('id')->all())->toBe(['perhubungan']);

    $actions = collect($ids)->map(function (string $id) use ($admin): string {
        $sections = $this->actingAs($admin)->get(route('admin.settings.edit', ['section' => $id]))->assertOk()->viewData('props')['sections'];

        expect($sections[0]['id'])->toBe($id);

        return collect($sections)->pluck('action');
    })->flatten();

    // Each form posts on its own, so saving one cannot disturb another.
    expect($actions->unique())->toHaveCount($actions->count());
});

it('never sends a saved secret back to the browser', function () {
    $sections = $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.settings.edit', ['section' => 'keselamatan']))
        ->viewData('props')['sections'];

    expect(collect($sections[0]['fields'])->firstWhere('name', 'secret_key')['value'])->toBe('');
});

it('answers 404 for a settings group that does not exist', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get('/admin/settings/tiada')
        ->assertNotFound();
});

it('brings the admin back to the group they saved', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->from(route('admin.settings.edit', ['section' => 'telegram']))
        ->put(route('admin.settings.telegram'), ['enabled' => '0', 'chat_id' => ''])
        ->assertRedirect(route('admin.settings.edit', ['section' => 'telegram']));
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

it('keeps the saved Telegram bot token when the field is left blank', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.telegram'), ['enabled' => '1', 'bot_token' => 'bot-token', 'chat_id' => '-100123'])
        ->assertSessionHasNoErrors();

    $this->actingAs($admin)
        ->put(route('admin.settings.telegram'), ['enabled' => '1', 'bot_token' => '', 'chat_id' => '-100999'])
        ->assertSessionHasNoErrors();

    $telegram = app(TelegramSettings::class);
    expect($telegram->botToken())->toBe('bot-token')
        ->and($telegram->chatId())->toBe('-100999')
        ->and($telegram->isEnabled())->toBeTrue();
});

it('keeps settings away from anyone who is not an admin', function () {
    $this->actingAs(User::factory()->create())
        ->put(route('admin.settings.contact'), ['phone' => '03-1234 5678'])
        ->assertForbidden();
});

it('starts every settings label with a capital letter', function () {
    $admin = User::factory()->admin()->create();

    $labels = collect(SettingController::SECTIONS)
        ->flatMap(fn (string $id): array => collect($this->actingAs($admin)->get(route('admin.settings.edit', ['section' => $id]))->viewData('props')['sections'])->flatMap(fn (array $section): array => $section['fields'])->all())
        ->pluck('label');

    expect($labels->reject(fn (string $label): bool => (bool) preg_match('/^\p{Lu}/u', $label))->values()->all())->toBe([]);
});

it('keeps Neekah Pro and the gateway that takes its payments on one page, apart from booking payments', function () {
    $admin = User::factory()->admin()->create();

    $pro = $this->actingAs($admin)->get(route('admin.settings.edit', ['section' => 'pro']))->viewData('props')['sections'];
    $bookings = $this->actingAs($admin)->get(route('admin.settings.edit', ['section' => 'bayaran']))->viewData('props')['sections'];

    expect(collect($pro)->pluck('id')->all())->toBe(['pro', 'gateway'])
        ->and(collect($bookings)->pluck('id')->all())->toBe(['bayaran'])
        // Only a method that works is offered for bookings.
        ->and(collect($bookings[0]['fields'])->pluck('name')->all())->toBe(['manual_transfer_enabled', 'instructions']);
});
