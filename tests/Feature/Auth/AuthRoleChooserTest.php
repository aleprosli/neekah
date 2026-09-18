<?php

use App\Enums\AuthAudience;
use App\Models\User;
use Database\Seeders\CategorySeeder;

it('asks pengantin or vendor before showing the login form', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('Log masuk ke Neekah')
        ->assertViewMissing('props')
        ->assertViewHas('chooser', fn (array $chooser): bool => collect($chooser['options'])->pluck('url')->all() === [
            AuthAudience::Couple->loginUrl(),
            AuthAudience::Vendor->loginUrl(),
        ]);
});

it('sends each sign-up card to its own form', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('Daftar akaun')
        ->assertViewHas('chooser', fn (array $chooser): bool => collect($chooser['options'])->pluck('url')->all() === [
            route('register', ['as' => 'pengantin']),
            route('vendor.register'),
        ]);
});

it('words the login form for a vendor and leaves Google off it', function () {
    // Google signs a newcomer up as a couple, which is the wrong account for
    // someone who came in through the vendor door.
    $this->get(route('login', ['as' => 'vendor']))
        ->assertOk()
        ->assertSee('Log masuk vendor')
        ->assertViewHas('props', fn (array $props): bool => $props['googleUrl'] === null
            && $props['links'][0]['url'] === route('vendor.register'));

    $this->get(route('login', ['as' => 'pengantin']))
        ->assertOk()
        ->assertViewHas('props', fn (array $props): bool => $props['googleUrl'] === route('auth.google'));
});

it('lets an account in whichever card was picked, and sends it to its own dashboard', function () {
    $couple = User::factory()->create(['password' => 'rahsia-kuat-123']);

    // The choice is wording, not a gate: a couple who tapped "Vendor" by
    // mistake still lands in their own dashboard.
    $this->from(route('login', ['as' => 'vendor']))
        ->post(route('login'), ['email' => $couple->email, 'password' => 'rahsia-kuat-123'])
        ->assertRedirect(route('dashboard'));
});

it('shows the form again, not the question, after a failed attempt', function () {
    $this->from(route('login'))
        ->post(route('login'), ['email' => 'tiada@example.com', 'password' => 'salah-sekali'])
        ->assertSessionHasErrors();

    $this->get(route('login'))->assertOk()->assertViewHas('props')->assertViewMissing('chooser');
});

it('keeps vendor sign-up indexable while login stays noindex', function () {
    $this->seed(CategorySeeder::class);

    $this->get(route('vendor.register'))->assertOk()->assertDontSee('content="noindex', false);
    $this->get(route('login'))->assertOk()->assertSee('name="robots" content="noindex, nofollow"', false);
});

it('draws sign-in in its own shell, without the public header or footer', function () {
    $html = $this->get(route('login'))->assertOk()->getContent();

    expect($html)->toContain('<meta name="page-shell" content="auth">')
        ->not->toContain('Cara Ia Berfungsi')
        ->not->toContain('aria-label="Footer"');
});

it('tells a couple one account is one person, and that the partner is invited afterwards', function () {
    $props = $this->get(route('register', ['as' => 'pengantin']))->assertOk()->viewData('props');
    $name = collect($props['fields'])->firstWhere('name', 'name');

    // The example used to read "Aina & Hakim", and people typed both names
    // into an account meant for one of them.
    expect($name['placeholder'])->not->toContain('&')
        ->and($name['help'])->toContain('kedua-duanya boleh')
        ->and($props['tip']['title'])->toBe('Satu akaun, dua pengantin')
        ->and($props['tip']['body'])->toContain('jemput pasangan anda');
});
