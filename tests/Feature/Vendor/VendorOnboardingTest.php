<?php

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('registers a vendor as pending and logs the owner in', function () {
    $category = Category::first();

    $this->post(route('vendor.register'), [
        'business_name' => 'ABC Wedding Photography',
        'category_id' => $category->id,
        'city' => 'Alor Setar',
        'state' => 'Kedah',
        'tagline' => 'Candid wedding photography',
        'name' => 'Ahmad Bakri',
        'phone' => '012-345 6789',
        'email' => 'abc@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
    ])->assertRedirect(route('vendor.dashboard'));

    $vendor = Vendor::sole();

    expect($vendor->slug)->toBe('abc-wedding-photography')
        ->and($vendor->status)->toBe(VendorStatus::Pending)
        ->and($vendor->tier)->toBe(VendorTier::New)
        ->and($vendor->user->role)->toBe(UserRole::Vendor);

    $this->assertAuthenticatedAs($vendor->user);
    $this->get(route('vendor.dashboard'))->assertOk()->assertSee('Menunggu kelulusan');
    $this->get(route('vendors.show', $vendor))->assertNotFound();
    $this->get('/')->assertDontSee('ABC Wedding Photography');
});

it('rejects duplicate business names and invalid states', function () {
    Vendor::factory()->for(Category::first())->create(['name' => 'Taken Studio']);

    $this->post(route('vendor.register'), [
        'business_name' => 'Taken Studio',
        'category_id' => Category::first()->id,
        'city' => 'Ipoh',
        'state' => 'Atlantis',
        'name' => 'Someone',
        'phone' => '0123',
        'email' => 'new@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
    ])->assertSessionHasErrors(['business_name', 'state']);
});

it('keeps customers and guests out of the vendor area', function () {
    $this->get(route('vendor.dashboard'))->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->get(route('vendor.dashboard'))->assertForbidden();
});

it('lets a vendor update their profile and upload a cover image', function () {
    Storage::fake('public');
    $vendor = Vendor::factory()->for(Category::first())->create();
    $newCategory = Category::where('slug', 'catering')->first();

    $this->actingAs($vendor->user)
        ->put(route('vendor.profile.update'), [
            'name' => 'Dapur Warisan',
            'category_id' => $newCategory->id,
            'tagline' => 'Masakan kampung',
            'description' => 'Katering untuk majlis besar.',
            'city' => 'Sungai Petani',
            'state' => 'Kedah',
            'phone' => '012-111 2222',
            'whatsapp' => '60121112222',
            'price_from' => 18,
            'price_unit' => 'pax',
            'cover_tone' => 'from-amber-500 to-orange-300',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 800, 600),
        ])
        ->assertRedirect(route('vendor.profile.edit'));

    $vendor->refresh();

    expect($vendor->name)->toBe('Dapur Warisan')
        ->and($vendor->category_id)->toBe($newCategory->id)
        ->and($vendor->price_unit->value)->toBe('pax')
        ->and($vendor->cover_image)->not->toBeNull();

    Storage::disk('public')->assertExists($vendor->cover_image);
});
