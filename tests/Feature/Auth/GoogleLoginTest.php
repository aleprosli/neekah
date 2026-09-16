<?php

use App\Enums\UserRole;
use App\Jobs\SendTelegramAlert;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\CustomerRegistered;
use App\Support\TelegramSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
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

it('holds a new Google customer at the phone form until they give a number', function () {
    Notification::fake();
    Queue::fake();
    app(TelegramSettings::class)->save(['enabled' => true, 'bot_token' => 'bot-token', 'chat_id' => '-100123']);

    fakeGoogleUser('aina@gmail.com');
    $this->get(route('auth.google.callback'));

    $user = User::sole();
    expect($user->phone)->toBeNull();

    // Nothing is announced yet: a lead without a number cannot be followed up.
    Notification::assertNothingSent();
    Queue::assertNotPushed(SendTelegramAlert::class);

    $this->get(route('dashboard'))->assertRedirect(route('phone.create'));
    $this->get(route('phone.create'))->assertOk()->assertSee('Nombor telefon');

    $this->post(route('phone.store'), ['phone' => 'bukan nombor'])->assertSessionHasErrors('phone');

    $this->post(route('phone.store'), ['phone' => '012-345 6789'])->assertRedirect(route('dashboard'));

    expect($user->fresh()->phone)->toBe('012-345 6789');

    Notification::assertSentTo($user, CustomerRegistered::class);
    Queue::assertPushed(SendTelegramAlert::class, function (SendTelegramAlert $job): bool {
        return str_contains((fn () => $this->text())->call($job), 'https://wa.me/60123456789');
    });

    $this->get(route('dashboard'))->assertOk();
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
