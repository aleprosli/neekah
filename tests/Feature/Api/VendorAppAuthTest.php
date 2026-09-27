<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\PersonalAccessToken;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->owner = User::factory()->vendor()->create(['email' => 'studio@example.com', 'password' => 'rahsia-kuat-123']);
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->for($this->owner)->create();
});

function signIn(array $overrides = []): TestResponse
{
    return test()->postJson(route('api.v1.auth.login'), [
        'email' => 'studio@example.com',
        'password' => 'rahsia-kuat-123',
        'device_name' => 'Pixel 9',
        ...$overrides,
    ]);
}

it('gives a Pro vendor a token that opens the app', function () {
    $response = signIn()->assertOk()
        ->assertJsonPath('token_type', 'Bearer')
        ->assertJsonPath('vendor.id', $this->vendor->id)
        ->assertJsonPath('vendor.is_pro', true);

    $token = $response->json('token');
    expect(PersonalAccessToken::sole()->name)->toBe('Pixel 9');

    $this->withToken($token)->getJson(route('api.v1.dashboard'))->assertOk()->assertJsonPath('vendor.name', $this->vendor->name);
});

it('refuses a wrong password, and locks out after five tries', function () {
    signIn(['password' => 'salah'])->assertUnprocessable()->assertJsonValidationErrors('email');

    foreach (range(1, 4) as $try) {
        signIn(['password' => 'salah']);
    }

    signIn()->assertUnprocessable()->assertJsonPath('errors.email.0', fn (string $message) => $message !== __('validation.custom.credentials'));
    expect(PersonalAccessToken::count())->toBe(0);
});

it('tells anyone who is not an approved Pro vendor why, and issues no token', function (Closure $arrange, string $code) {
    $arrange($this);

    signIn()->assertForbidden()->assertJsonPath('code', $code);

    expect(PersonalAccessToken::count())->toBe(0);
})->with([
    'basic vendor' => [fn ($test) => $test->vendor->update(['pro_until' => null]), 'pro_required'],
    'expired Pro' => [fn ($test) => $test->vendor->update(['pro_until' => now()->subDay()]), 'pro_required'],
    'awaiting approval' => [fn ($test) => $test->vendor->update(['status' => 'pending']), 'not_approved'],
    'couple' => [fn ($test) => $test->owner->update(['role' => 'customer']), 'not_vendor'],
    'deactivated' => [fn ($test) => $test->owner->update(['deactivated_at' => now()]), 'account_inactive'],
]);

it('points a vendor without Pro at the page to buy it', function () {
    $this->vendor->update(['pro_until' => null]);

    signIn()->assertJsonPath('pro_url', route('vendor.pro.index'));
});

it('asks for the access code wherever the site is closed', function () {
    config(['neekah.access_code' => 'kod-dalaman-123']);

    $this->getJson(route('api.v1.auth.config'))->assertOk()->assertJsonPath('access_code_required', true);
    signIn()->assertUnprocessable()->assertJsonValidationErrors('access_code');
    signIn(['access_code' => 'kod-dalaman-123'])->assertOk();
});

it('locks the app once Pro ends, but still says who is signed in', function () {
    $token = signIn()->json('token');
    $this->vendor->update(['pro_until' => now()->subMinute()]);

    $this->withToken($token)->getJson(route('api.v1.bookings.index'))->assertForbidden()->assertJsonPath('code', 'pro_required');
    $this->withToken($token)->getJson(route('api.v1.me'))->assertOk()->assertJsonPath('pro.active', false);
});

it('signs a deactivated account out of the app', function () {
    $token = signIn()->json('token');
    $this->owner->update(['deactivated_at' => now()]);

    $this->withToken($token)->getJson(route('api.v1.dashboard'))->assertUnauthorized()->assertJsonPath('code', 'account_inactive');
    expect(PersonalAccessToken::count())->toBe(0);
});

it('revokes the token on sign out', function () {
    $token = signIn()->json('token');

    $this->withToken($token)->postJson(route('api.v1.auth.logout'))->assertOk();

    expect(PersonalAccessToken::count())->toBe(0);
});

it('answers without a token with 401, never a login page', function () {
    $this->getJson(route('api.v1.dashboard'))->assertUnauthorized();
    $this->get(route('api.v1.dashboard'))->assertUnauthorized();
});

it('speaks the language the app asks for, and Malay otherwise', function (string $header, string $locale) {
    $this->vendor->update(['pro_until' => null]);

    $this->withHeader('Accept-Language', $header)->postJson(route('api.v1.auth.login'), [
        'email' => 'studio@example.com', 'password' => 'rahsia-kuat-123', 'device_name' => 'Pixel 9',
    ])->assertJsonPath('message', __('api.errors.pro_required', [], $locale));
})->with([
    'malay' => ['ms', 'ms'],
    'english' => ['en', 'en'],
    'anything else' => ['fr-FR', 'ms'],
]);
