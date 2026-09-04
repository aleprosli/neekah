<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('renders the forgot password page', function () {
    $this->get(route('password.request'))->assertOk()->assertSee('Lupa kata laluan');
});

it('emails a reset link to a registered user', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

it('gives the same response for an unknown email without sending anything', function () {
    Notification::fake();

    $this->post(route('password.email'), ['email' => 'nobody@example.com'])
        ->assertSessionHas('status')
        ->assertSessionHasNoErrors();

    Notification::assertNothingSent();
});

it('resets the password with a valid token and lets the user log in', function () {
    Notification::fake();
    $user = User::factory()->create(['password' => 'lama-punya-123']);

    $this->post(route('password.email'), ['email' => $user->email]);

    $token = null;
    Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$token) {
        $token = $notification->token;

        return true;
    });

    $this->get(route('password.reset', $token))->assertOk()->assertSee('Set kata laluan baharu');

    $this->post(route('password.store'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'baharu-kuat-123',
        'password_confirmation' => 'baharu-kuat-123',
    ])->assertRedirect(route('login'));

    $this->post(route('login'), ['email' => $user->email, 'password' => 'baharu-kuat-123'])->assertRedirect();
    $this->assertAuthenticatedAs($user);
});

it('rejects an invalid token, a mismatched confirmation and a weak password', function () {
    $user = User::factory()->create();

    $this->post(route('password.store'), [
        'token' => 'token-palsu',
        'email' => $user->email,
        'password' => 'baharu-kuat-123',
        'password_confirmation' => 'baharu-kuat-123',
    ])->assertSessionHasErrors('email');

    $this->post(route('password.store'), [
        'token' => Password::createToken($user),
        'email' => $user->email,
        'password' => 'pendek',
        'password_confirmation' => 'lain',
    ])->assertSessionHasErrors('password');
});
