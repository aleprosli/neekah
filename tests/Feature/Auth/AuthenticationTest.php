<?php

use App\Enums\UserRole;
use App\Models\User;

it('renders the login and register pages for guests', function () {
    $this->get(route('login'))->assertOk()->assertSee('Log masuk');
    $this->get(route('register'))->assertOk()->assertSee('Daftar akaun');
});

it('registers a customer and logs them in', function () {
    $this->post(route('register'), [
        'name' => 'Aina Zulkifli',
        'email' => 'aina@example.com',
        'phone' => '012-345 6789',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
    ])->assertRedirect(route('vendors.index'));

    $user = User::where('email', 'aina@example.com')->sole();

    expect($user->role)->toBe(UserRole::Customer);
    $this->assertAuthenticatedAs($user);
});

it('rejects registration with a duplicate email or weak password', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->post(route('register'), [
        'name' => 'Someone',
        'email' => 'taken@example.com',
        'password' => 'short',
        'password_confirmation' => 'short',
    ])->assertSessionHasErrors(['email', 'password']);

    $this->assertGuest();
});

it('logs in with valid credentials and rejects invalid ones', function () {
    $user = User::factory()->create(['password' => 'rahsia-kuat-123']);

    $this->post(route('login'), ['email' => $user->email, 'password' => 'salah'])
        ->assertSessionHasErrors('email');
    $this->assertGuest();

    $this->post(route('login'), ['email' => $user->email, 'password' => 'rahsia-kuat-123'])
        ->assertRedirect(route('vendors.index'));
    $this->assertAuthenticatedAs($user);
});

it('sends authenticated users back to their intended page after login', function () {
    $user = User::factory()->create(['password' => 'rahsia-kuat-123']);

    $this->get(route('bookings.index'))->assertRedirect(route('login'));

    $this->post(route('login'), ['email' => $user->email, 'password' => 'rahsia-kuat-123'])
        ->assertRedirect(route('bookings.index'));
});

it('logs out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect(route('vendors.index'));
    $this->assertGuest();
});

it('keeps logged-in users away from the guest pages', function () {
    $this->actingAs(User::factory()->create())->get(route('login'))->assertRedirect();
});
