<?php

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

/**
 * @return array<string, mixed>
 */
function businessDetails(array $overrides = []): array
{
    return [
        'business_name' => 'ABC Wedding Photography',
        'category_id' => Category::first()->id,
        'city' => 'Alor Setar',
        'state' => 'Kedah',
        'phone' => '012-345 6789',
        ...$overrides,
    ];
}

it('switches a couple account with no activity to a pending vendor', function () {
    $user = User::factory()->create(['email' => 'abc@example.com']);

    $this->actingAs($user)->get(route('dashboard'))->assertSee(route('vendor.convert'));
    $this->actingAs($user)->get(route('vendor.convert'))->assertOk()->assertSee('abc@example.com');

    $this->actingAs($user)
        ->post(route('vendor.convert'), businessDetails())
        ->assertRedirect(route('vendor.dashboard'));

    $vendor = Vendor::sole();

    expect($vendor->user_id)->toBe($user->id)
        ->and($vendor->status)->toBe(VendorStatus::Pending)
        ->and($vendor->phone)->toBe('012-345 6789')
        ->and($user->fresh()->role)->toBe(UserRole::Vendor);

    $this->get(route('vendor.dashboard'))->assertOk();
});

it('sends a couple account with activity to admin instead', function (Closure $giveActivity) {
    $user = User::factory()->create();
    $giveActivity($user);

    $this->actingAs($user)->get(route('dashboard'))->assertDontSee(route('vendor.convert'));
    $this->actingAs($user)->get(route('vendor.convert'))
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'hubungi admin'));
    $this->actingAs($user)->post(route('vendor.convert'), businessDetails())->assertForbidden();

    expect($user->vendor()->exists())->toBeFalse()
        ->and($user->fresh()->role)->toBe(UserRole::Customer);
})->with([
    'a wedding' => fn (User $user) => Wedding::factory()->for($user)->create(),
    'an enquiry' => fn (User $user) => Enquiry::factory()->for($user)->for(Vendor::factory()->for(Category::first()))->create(),
]);

it('does not let a vendor or admin convert again', function () {
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($vendor->user)->get(route('vendor.convert'))->assertRedirect(route('vendor.dashboard'));
    $this->actingAs($vendor->user)->post(route('vendor.convert'), businessDetails())->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->post(route('vendor.convert'), businessDetails())->assertForbidden();
});

it('requires a guest to sign in before converting', function () {
    $this->get(route('vendor.convert'))->assertRedirect(route('login'));
});

it('tells a couple signing up as a vendor how to switch instead of only saying the email is taken', function () {
    User::factory()->create(['email' => 'abc@example.com']);

    $this->post(route('vendor.register'), [
        ...businessDetails(),
        'name' => 'Ahmad Bakri',
        'email' => 'abc@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
    ])
        ->assertSessionHasErrors('existing_customer')
        ->assertSessionDoesntHaveErrors('email');

    expect(Vendor::count())->toBe(0);
});

it('still rejects an email already used by a vendor account', function () {
    User::factory()->vendor()->create(['email' => 'abc@example.com']);

    $this->post(route('vendor.register'), [
        ...businessDetails(),
        'name' => 'Ahmad Bakri',
        'email' => 'abc@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
    ])
        ->assertSessionHasErrors('email')
        ->assertSessionDoesntHaveErrors('existing_customer');
});
