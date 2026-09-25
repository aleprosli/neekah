<?php

use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('walks a new vendor through what is missing and what each photo is for', function () {
    $vendor = Vendor::factory()->pending()->for(Category::first())->create([
        'tagline' => null,
        'description' => null,
        'cover_image' => null,
        'price_from' => 0,
    ]);

    $this->actingAs($vendor->user)
        ->get(route('vendor.dashboard'))
        ->assertOk()
        ->assertViewIs('vendor.setup')
        ->assertViewHas('steps', function (Collection $steps): bool {
            $keys = $steps->pluck('key')->all();
            $done = $steps->pluck('done')->all();

            // Every step explains itself; a bare checklist does not tell a
            // vendor what a portfolio photo is even for.
            return $keys === ['profil', 'cover', 'portfolio', 'pakej', 'harga']
                && $done === array_fill(0, 5, false)
                && collect($steps)->every(fn (array $step): bool => filled($step['why']) && filled($step['href']));
        });
});

it('registers a vendor as pending and logs the owner in', function () {
    $category = Category::first();

    $this->post(route('vendor.register'), [
        'business_name' => 'ABC Wedding Photography',
        'category_id' => $category->id,
        'city' => 'Alor Setar',
        'district' => 'Kota Setar',
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
        ->and($vendor->city)->toBe('Alor Setar')
        ->and($vendor->district)->toBe('Kota Setar')
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

/**
 * @return array<string, mixed>
 */
function vendorSignup(array $overrides = []): array
{
    return [
        'business_name' => 'ABC Wedding Photography',
        'category_id' => Category::first()->id,
        'city' => 'Alor Setar',
        'district' => 'Kota Setar',
        'state' => 'Kedah',
        'name' => 'Ahmad Bakri',
        'phone' => '012-345 6789',
        'email' => 'abc@example.com',
        'password' => 'rahsia-kuat-123',
        'password_confirmation' => 'rahsia-kuat-123',
        ...$overrides,
    ];
}

it('stores the vendor phone in international form, from any country', function (string $typed, string $stored) {
    $this->post(route('vendor.register'), vendorSignup(['phone' => $typed]))
        ->assertRedirect(route('vendor.dashboard'));

    expect(Vendor::sole()->phone)->toBe($stored)
        ->and(Vendor::sole()->user->phone)->toBe($stored);
})->with([
    'Malaysian, typed locally' => ['012-345 6789', '+60123456789'],
    'Malaysian, with the code' => ['+60 12-345 6789', '+60123456789'],
    'Singaporean' => ['+65 9123 4567', '+6591234567'],
]);

it('refuses a phone number that is not a real number', function () {
    $this->post(route('vendor.register'), vendorSignup(['phone' => '0123']))
        ->assertSessionHasErrors(['phone' => 'Medan nombor telefon mesti nombor telefon yang sah untuk negara yang dipilih.']);

    expect(Vendor::count())->toBe(0);
});

it('only accepts a daerah of the negeri the vendor picked', function () {
    $this->post(route('vendor.register'), vendorSignup(['state' => 'Kedah', 'district' => 'Kinta']))
        ->assertSessionHasErrors('district')
        ->assertSessionDoesntHaveErrors('city');

    expect(Vendor::count())->toBe(0);
});

it('hands the signup form each negeri with its own daerah', function () {
    $this->get(route('vendor.register'))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $kedah = $props['districts']['Kedah'];

            return $kedah['label'] === 'Daerah'
                && in_array('Kota Setar', $kedah['options'], true)
                && ! in_array('Kinta', $kedah['options'], true)
                && $props['districts']['Kelantan']['label'] === 'Jajahan'
                && in_array('Bau', $props['districts']['Sarawak']['options'], true);
        });
});

it('lets an existing vendor add a daerah in their profile without touching their city', function () {
    $vendor = Vendor::factory()->for(Category::first())->create(['city' => 'Sungai Petani', 'state' => 'Kedah', 'district' => null]);
    $profile = fn (array $overrides): array => [
        'name' => $vendor->name,
        'category_id' => $vendor->category_id,
        'city' => 'Sungai Petani',
        'state' => 'Kedah',
        'price_from' => 1000,
        'price_unit' => 'package',
        'cover_tone' => 'from-amber-500 to-orange-300',
        ...$overrides,
    ];

    $this->actingAs($vendor->user)
        ->put(route('vendor.profile.update'), $profile(['district' => 'Kinta']))
        ->assertSessionHasErrors('district');

    $this->actingAs($vendor->user)
        ->put(route('vendor.profile.update'), $profile(['district' => 'Kuala Muda']))
        ->assertSessionHasNoErrors();

    expect($vendor->refresh())
        ->district->toBe('Kuala Muda')
        ->city->toBe('Sungai Petani');
});
