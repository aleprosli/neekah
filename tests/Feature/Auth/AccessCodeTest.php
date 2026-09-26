<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    config(['services.google.client_id' => 'google-client-id']);
});

function closeWithAccessCode(): void
{
    config(['neekah.access_code' => 'kod-dalaman-123']);
}

$couple = fn (array $overrides = []): array => [
    'name' => 'Aina Zulkifli',
    'email' => 'aina@example.com',
    'password' => 'rahsia-kuat-123',
    'password_confirmation' => 'rahsia-kuat-123',
    ...$overrides,
];

it('asks for nothing extra and offers Google while no access code is set', function () {
    $props = $this->get(route('login', ['as' => 'pengantin']))->assertOk()->viewData('props');

    expect(collect($props['fields'])->pluck('name'))->not->toContain('access_code')
        ->and($props['googleUrl'])->toBe(route('auth.google'));
});

it('leaves Google off when it is not configured', function () {
    config(['services.google.client_id' => null]);

    $props = $this->get(route('register', ['as' => 'pengantin']))->assertOk()->viewData('props');

    expect($props['googleUrl'])->toBeNull();
});

it('asks for the code and hides Google on every sign-in and sign-up form', function () {
    closeWithAccessCode();
    $this->seed(CategorySeeder::class);

    $login = $this->get(route('login', ['as' => 'pengantin']))->assertOk()->viewData('props');
    $register = $this->get(route('register', ['as' => 'pengantin']))->assertOk()->viewData('props');
    $vendor = $this->get(route('vendor.register'))->assertOk()->viewData('props');

    expect(collect($login['fields'])->pluck('name'))->toContain('access_code')
        ->and(collect($register['fields'])->pluck('name'))->toContain('access_code')
        ->and($vendor['accessCode']['name'])->toBe('access_code')
        ->and($login['googleUrl'])->toBeNull()
        ->and($register['googleUrl'])->toBeNull();
});

it('refuses a sign-in without the right code, even with the right password', function (?string $code) {
    closeWithAccessCode();
    $user = User::factory()->create(['password' => 'rahsia-kuat-123']);

    $this->post(route('login'), array_filter(['email' => $user->email, 'password' => 'rahsia-kuat-123', 'access_code' => $code]))
        ->assertSessionHasErrors(['access_code' => 'Kod akses tidak sah.']);

    $this->assertGuest();
})->with(['missing' => null, 'wrong' => 'teka-teka']);

it('signs in with the right code', function () {
    closeWithAccessCode();
    $user = User::factory()->create(['password' => 'rahsia-kuat-123']);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'rahsia-kuat-123', 'access_code' => 'kod-dalaman-123'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

it('registers a couple only with the right code', function () use ($couple) {
    closeWithAccessCode();

    $this->post(route('register'), $couple())->assertSessionHasErrors('access_code');
    expect(User::count())->toBe(0);

    $this->post(route('register'), $couple(['access_code' => 'kod-dalaman-123']))->assertRedirect(route('dashboard'));
    expect(User::sole()->email)->toBe('aina@example.com');
});

it('registers a vendor only with the right code', function () {
    closeWithAccessCode();
    $this->seed(CategorySeeder::class);

    $vendor = fn (array $overrides = []): array => [
        'business_name' => 'ABC Wedding Photography',
        'category_id' => Category::first()->id,
        'city' => 'Alor Setar',
        'district' => 'Kota Setar',
        'state' => 'Kedah',
        'name' => 'Ahmad Bakri',
        'phone' => '012-345 6789',
        'email' => 'abc@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
        ...$overrides,
    ];

    $this->post(route('vendor.register'), $vendor())->assertSessionHasErrors('access_code');
    expect(Vendor::count())->toBe(0);

    $this->post(route('vendor.register'), $vendor(['access_code' => 'kod-dalaman-123']))->assertRedirect(route('vendor.dashboard'));
    expect(Vendor::sole()->name)->toBe('ABC Wedding Photography');
});

it('closes both Google routes, since Google never asks for the code', function (string $route) {
    closeWithAccessCode();

    $this->get(route($route))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => 'Log masuk dengan Google tidak tersedia.']);

    $this->assertGuest();
    expect(User::count())->toBe(0);
})->with(['auth.google', 'auth.google.callback']);
