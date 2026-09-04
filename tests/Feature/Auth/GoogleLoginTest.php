<?php

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

function fakeGoogleUser(string $email, string $id = 'google-123', string $name = 'Aina Zulkifli'): void
{
    $user = (new SocialiteUser)->map([
        'id' => $id,
        'name' => $name,
        'email' => $email,
        'avatar' => 'https://lh3.googleusercontent.com/a/photo',
    ]);

    Socialite::shouldReceive('driver->user')->andReturn($user);
}

it('registers a new customer from a Google account', function () {
    fakeGoogleUser('aina@gmail.com');

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    $user = User::sole();

    expect($user->email)->toBe('aina@gmail.com')
        ->and($user->google_id)->toBe('google-123')
        ->and($user->role)->toBe(UserRole::Customer)
        ->and($user->avatar_url)->toContain('googleusercontent')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and($user->password)->toBeNull();

    $this->assertAuthenticatedAs($user);
});

it('links Google to an existing account by email instead of duplicating it', function () {
    $existing = User::factory()->create(['email' => 'aina@gmail.com', 'google_id' => null]);
    fakeGoogleUser('aina@gmail.com');

    $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

    expect(User::count())->toBe(1)
        ->and($existing->fresh()->google_id)->toBe('google-123');

    $this->assertAuthenticatedAs($existing);
});

it('sends a vendor to their own dashboard', function () {
    $this->seed(CategorySeeder::class);
    $vendor = Vendor::factory()->for(Category::first())->create();
    fakeGoogleUser($vendor->user->email);

    $this->get(route('auth.google.callback'))->assertRedirect(route('vendor.dashboard'));
});

it('redirects back to login when Google fails or hides the email', function () {
    Socialite::shouldReceive('driver->user')->andThrow(new RuntimeException('denied'));

    $this->get(route('auth.google.callback'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('explains that Google login is unconfigured when no client id is set', function () {
    config(['services.google.client_id' => null]);

    $this->get(route('auth.google'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');
});
