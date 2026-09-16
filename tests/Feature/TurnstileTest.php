<?php

use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\User;
use App\Models\Vendor;
use App\Support\TurnstileSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function enableTurnstile(): void
{
    app(TurnstileSettings::class)->save(['enabled' => true, 'site_key' => 'site-key', 'secret_key' => 'secret-key']);
}

$registration = fn (array $overrides = []): array => [
    'name' => 'Aina',
    'email' => 'aina@example.com',
    'password' => 'kata-laluan-kuat',
    'password_confirmation' => 'kata-laluan-kuat',
    ...$overrides,
];

it('does not ask for a token while Turnstile is switched off', function () use ($registration) {
    Http::fake();

    $this->post(route('register'), $registration())->assertSessionHasNoErrors();

    Http::assertNothingSent();
});

it('shows the widget on the register form once Turnstile is on', function () {
    enableTurnstile();

    // The form is a component, so the key travels in its props and the loader
    // script has to be on the page for the widget to appear at all.
    $response = $this->get(route('register'))->assertOk();

    expect($response->viewData('props')['turnstileSiteKey'])->toBe('site-key');
    $response->assertSee('challenges.cloudflare.com/turnstile/v0/api.js');
});

it('puts the widget on the vendor register form too', function () {
    enableTurnstile();
    $this->seed(CategorySeeder::class);

    $response = $this->get(route('vendor.register'))->assertOk();

    // The form is a Vue component now, so the key travels in its props and the
    // loader script still has to be on the page for the widget to appear.
    expect($response->viewData('props')['turnstileSiteKey'])->toBe('site-key');
    $response->assertSee('challenges.cloudflare.com/turnstile/v0/api.js');
});

it('turns a missing token into a form error', function () use ($registration) {
    enableTurnstile();
    Http::fake();

    $this->post(route('register'), $registration())->assertSessionHasErrors('cf-turnstile-response');

    Http::assertNothingSent();
});

it('rejects a token Cloudflare does not recognise', function () use ($registration) {
    enableTurnstile();
    Http::fake([TurnstileSettings::VERIFY_URL => Http::response(['success' => false])]);

    $this->post(route('register'), $registration(['cf-turnstile-response' => 'bad-token']))
        ->assertSessionHasErrors('cf-turnstile-response');
});

it('accepts a token Cloudflare confirms', function () use ($registration) {
    enableTurnstile();
    Http::fake([TurnstileSettings::VERIFY_URL => Http::response(['success' => true])]);

    $this->post(route('register'), $registration(['cf-turnstile-response' => 'good-token']))
        ->assertSessionHasNoErrors();

    Http::assertSent(fn ($request) => $request['secret'] === 'secret-key' && $request['response'] === 'good-token');
});

it('lets the form through when Cloudflare cannot be reached', function () use ($registration) {
    enableTurnstile();
    Http::fake(fn () => throw new ConnectionException('timed out'));

    $this->post(route('register'), $registration(['cf-turnstile-response' => 'any-token']))
        ->assertSessionHasNoErrors();
});

it('guards the login form as well', function () {
    enableTurnstile();
    $user = User::factory()->create();
    Http::fake([TurnstileSettings::VERIFY_URL => Http::response(['success' => true])]);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password'])
        ->assertSessionHasErrors('cf-turnstile-response');

    $this->assertGuest();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'password', 'cf-turnstile-response' => 'good-token'])
        ->assertSessionHasNoErrors();

    $this->assertAuthenticatedAs($user);
});

it('guards the booking form on a vendor page', function () {
    $this->seed(CategorySeeder::class);
    $customer = User::factory()->create();
    $vendor = Vendor::factory()->for(Category::first())->create();
    $package = Package::factory()->for($vendor)->create();

    enableTurnstile();
    Http::fake([TurnstileSettings::VERIFY_URL => Http::response(['success' => false])]);

    $this->actingAs($customer)
        ->post(route('vendors.bookings.store', $vendor), [
            'package_id' => $package->id,
            'event_date' => now()->addMonths(3)->toDateString(),
            'cf-turnstile-response' => 'bad-token',
        ])
        ->assertSessionHasErrors('cf-turnstile-response');

    expect(Booking::count())->toBe(0);
});
