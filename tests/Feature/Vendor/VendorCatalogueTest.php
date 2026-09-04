<?php

use App\Models\Category;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Models\VendorUnavailableDate;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->for(Category::first())->create(['price_from' => 9999]);
});

it('creates, updates and deletes packages and syncs the starting price', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.packages.store'), [
            'name' => 'Premium Package',
            'price' => 2500,
            'duration' => '10 jam',
            'features' => "2 photographers\n500 edited photos\n\nHighlight video",
            'is_active' => 1,
        ])
        ->assertRedirect(route('vendor.packages.index'));

    $package = Package::sole();
    expect($package->features)->toBe(['2 photographers', '500 edited photos', 'Highlight video'])
        ->and((float) $this->vendor->fresh()->price_from)->toBe(2500.0);

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.packages.update', $package), ['name' => 'Basic', 'price' => 1500, 'features' => '1 photographer', 'is_active' => 1])
        ->assertRedirect();

    expect((float) $this->vendor->fresh()->price_from)->toBe(1500.0);

    $this->actingAs($this->vendor->user)->delete(route('vendor.packages.destroy', $package))->assertRedirect();
    expect(Package::count())->toBe(0);
});

it('stops a vendor from editing another vendor package', function () {
    $other = Package::factory()->create();

    $this->actingAs($this->vendor->user)->get(route('vendor.packages.edit', $other))->assertForbidden();
    $this->actingAs($this->vendor->user)->put(route('vendor.packages.update', $other), ['name' => 'X', 'price' => 1, 'features' => 'y'])->assertForbidden();
});

it('uploads and deletes portfolio images', function () {
    Storage::fake('public');

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.portfolio.store'), [
            'images' => [UploadedFile::fake()->image('a.jpg'), UploadedFile::fake()->image('b.png')],
            'caption' => 'Majlis Aina & Hakim',
        ])
        ->assertRedirect(route('vendor.portfolio.index'));

    $items = PortfolioItem::all();
    expect($items)->toHaveCount(2);
    Storage::disk('public')->assertExists($items->first()->path);

    $this->actingAs($this->vendor->user)->delete(route('vendor.portfolio.destroy', $items->first()))->assertRedirect();
    Storage::disk('public')->assertMissing($items->first()->path);
    expect(PortfolioItem::count())->toBe(1);
});

it('blocks a date range and reopens a date', function () {
    $from = now()->addMonth()->toDateString();
    $to = now()->addMonth()->addDays(2)->toDateString();

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.availability.store'), ['from' => $from, 'to' => $to, 'reason' => 'Cuti'])
        ->assertRedirect(route('vendor.availability.index'));

    expect(VendorUnavailableDate::count())->toBe(3)
        ->and($this->vendor->isAvailableOn($from))->toBeFalse();

    $date = VendorUnavailableDate::first();
    $this->actingAs($this->vendor->user)->delete(route('vendor.availability.destroy', $date))->assertRedirect();
    expect($this->vendor->isAvailableOn($date->date))->toBeTrue();
});
