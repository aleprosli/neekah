<?php

use App\Support\TurnstileSettings;
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

    $this->get(route('register'))
        ->assertSee('cf-turnstile')
        ->assertSee('data-sitekey="site-key"', false)
        ->assertSee('challenges.cloudflare.com/turnstile/v0/api.js');
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
