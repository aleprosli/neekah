<?php

use App\Models\Category;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->for(Category::first())->create(['name' => 'Bride Assistant']);
});

/** The fields the profile form always posts, so a test can vary just one. */
function profilePayload(Vendor $vendor, array $overrides = []): array
{
    return [
        'name' => $vendor->name,
        'category_id' => $vendor->category_id,
        'city' => $vendor->city,
        'state' => $vendor->state,
        'price_from' => $vendor->price_from,
        'price_unit' => $vendor->price_unit->value,
        'cover_tone' => $vendor->cover_tone,
        ...$overrides,
    ];
}

it('shows the initial until a logo is uploaded, then the logo', function () {
    Storage::fake('public');

    // No logo: the public page falls back to the first letter of the business.
    $this->get(route('vendors.show', $this->vendor))->assertOk()->assertDontSee('Logo Bride Assistant');

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profilePayload($this->vendor, [
            'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
        ]))
        ->assertRedirect(route('vendor.profile.edit'));

    $logo = $this->vendor->fresh()->logo;
    expect($logo)->not->toBeNull();
    Storage::disk('public')->assertExists($logo);

    $this->get(route('vendors.show', $this->vendor))->assertOk()->assertSee('Logo Bride Assistant');
});

it('replaces the old logo file rather than leaving it behind', function () {
    Storage::fake('public');

    $this->actingAs($this->vendor->user)->put(route('vendor.profile.update'), profilePayload($this->vendor, [
        'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
    ]));

    $first = $this->vendor->fresh()->logo;

    $this->actingAs($this->vendor->user)->put(route('vendor.profile.update'), profilePayload($this->vendor, [
        'logo' => UploadedFile::fake()->image('baru.png', 512, 512),
    ]));

    expect($this->vendor->fresh()->logo)->not->toBe($first);
    Storage::disk('public')->assertMissing($first);
});

it('goes back to the initial when the vendor removes the logo', function () {
    Storage::fake('public');

    $this->actingAs($this->vendor->user)->put(route('vendor.profile.update'), profilePayload($this->vendor, [
        'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
    ]));

    $path = $this->vendor->fresh()->logo;

    $this->actingAs($this->vendor->user)->put(route('vendor.profile.update'), profilePayload($this->vendor, ['remove_logo' => 1]));

    expect($this->vendor->fresh()->logo)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('keeps the logo when the profile is saved without touching it', function () {
    Storage::fake('public');

    $this->actingAs($this->vendor->user)->put(route('vendor.profile.update'), profilePayload($this->vendor, [
        'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
    ]));

    $path = $this->vendor->fresh()->logo;

    $this->actingAs($this->vendor->user)->put(route('vendor.profile.update'), profilePayload($this->vendor, ['tagline' => 'Pembantu hari perkahwinan anda']));

    expect($this->vendor->fresh()->logo)->toBe($path);
});
