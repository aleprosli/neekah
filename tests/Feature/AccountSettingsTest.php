<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('opens for every role, inside that role dashboard', function (Closure $signIn, string $sidebarLabel) {
    $this->actingAs($signIn())
        ->get(route('account.edit'))
        ->assertOk()
        ->assertSee('Akaun saya')
        ->assertSee($sidebarLabel);
})->with([
    'admin' => [fn () => User::factory()->admin()->create(), 'Log sistem'],
    'vendor' => [fn () => Vendor::factory()->for(Category::first())->create()->user, 'Point & Ranking'],
    'couple' => [fn () => User::factory()->create(), 'Checklist'],
]);

it('requires signing in', function () {
    $this->get(route('account.edit'))->assertRedirect(route('login'));
    $this->put(route('account.update'), [])->assertRedirect(route('login'));
});

it('updates name and phone without asking for a password', function () {
    $user = User::factory()->create(['name' => 'Aina', 'phone' => '011-111 1111']);

    $this->actingAs($user)
        ->put(route('account.update'), ['name' => 'Aina Zulkifli', 'phone' => '012-345 6789', 'email' => $user->email])
        ->assertRedirect(route('account.edit'));

    expect($user->fresh()->name)->toBe('Aina Zulkifli')
        ->and($user->fresh()->phone)->toBe('012-345 6789');
});

it('only moves the account to a new email once the current password is given', function () {
    $user = User::factory()->create(['email' => 'lama@example.com', 'password' => 'rahsia-kuat-123']);

    $this->actingAs($user)
        ->put(route('account.update'), ['name' => $user->name, 'email' => 'baru@example.com'])
        ->assertSessionHasErrors('current_password');

    expect($user->fresh()->email)->toBe('lama@example.com');

    $this->actingAs($user)
        ->put(route('account.update'), ['name' => $user->name, 'email' => 'baru@example.com', 'current_password' => 'rahsia-kuat-123'])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('baru@example.com');
});

it('refuses an email another account already uses', function () {
    $user = User::factory()->create(['password' => 'rahsia-kuat-123']);
    $other = User::factory()->create();

    $this->actingAs($user)
        ->put(route('account.update'), ['name' => $user->name, 'email' => $other->email, 'current_password' => 'rahsia-kuat-123'])
        ->assertSessionHasErrors('email');
});

it('changes the password only with the current one', function () {
    $user = User::factory()->create(['password' => 'rahsia-kuat-123']);

    $this->actingAs($user)
        ->put(route('account.password'), ['current_password' => 'salah', 'password' => 'rahsia-baru-456', 'password_confirmation' => 'rahsia-baru-456'])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('rahsia-kuat-123', $user->fresh()->password))->toBeTrue();

    $this->actingAs($user)
        ->put(route('account.password'), ['current_password' => 'rahsia-kuat-123', 'password' => 'rahsia-baru-456', 'password_confirmation' => 'rahsia-baru-456'])
        ->assertRedirect(route('account.edit'));

    expect(Hash::check('rahsia-baru-456', $user->fresh()->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

it('lets a Google account set a first password without one', function () {
    $user = User::factory()->create(['google_id' => 'g-123', 'password' => null]);

    $this->actingAs($user)->get(route('account.edit'))->assertOk()->assertSee('Tetapkan kata laluan');

    $this->actingAs($user)
        ->put(route('account.password'), ['password' => 'rahsia-kuat-123', 'password_confirmation' => 'rahsia-kuat-123'])
        ->assertRedirect(route('account.edit'));

    expect(Hash::check('rahsia-kuat-123', $user->fresh()->password))->toBeTrue();
});
