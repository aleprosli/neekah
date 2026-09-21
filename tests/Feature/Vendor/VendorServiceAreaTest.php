<?php

use App\Http\Requests\UpdateVendorProfileRequest;
use App\Models\Category;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->photography = Category::where('slug', 'photography')->first();
    $this->catering = Category::where('slug', 'catering')->first();
    $this->vendor = Vendor::factory()->for($this->photography)->create(['state' => 'Selangor']);
});

/** The fields the profile form always posts, plus whatever is under test. */
function profileWith(Vendor $vendor, array $overrides): array
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

it('saves every category the vendor works in and every negeri they cover', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWith($this->vendor, [
            'category_ids' => [$this->catering->id],
            'service_states' => ['Johor', 'Melaka'],
        ]))
        ->assertSessionHasNoErrors();

    $vendor = $this->vendor->fresh();

    expect($vendor->categories->pluck('slug')->sort()->values()->all())->toBe(['catering', 'photography']);
    expect($vendor->serviceStates())->toBe(['Selangor', 'Johor', 'Melaka']);
});

it('keeps the primary category and the home state in the lists whatever is posted', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWith($this->vendor, [
            'category_ids' => [$this->catering->id],
            'service_states' => ['Johor'],
        ]))
        ->assertSessionHasNoErrors();

    // Neither list was posted with the vendor's own category or state in it.
    $vendor = $this->vendor->fresh();

    expect($vendor->categories->pluck('id'))->toContain($vendor->category_id);
    expect($vendor->serviceStates())->toContain('Selangor');
});

it('drops a category the vendor unticks, but never the primary one', function () {
    $this->vendor->categories()->attach($this->catering);

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWith($this->vendor, ['category_ids' => []]))
        ->assertSessionHasNoErrors();

    expect($this->vendor->fresh()->categories->pluck('slug')->all())->toBe(['photography']);
});

it('refuses more categories than a vendor may claim', function () {
    $tooMany = Category::query()
        ->whereKeyNot($this->photography)
        ->limit(UpdateVendorProfileRequest::MAX_CATEGORIES)
        ->pluck('id')
        ->all();

    expect($tooMany)->toHaveCount(UpdateVendorProfileRequest::MAX_CATEGORIES);

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWith($this->vendor, ['category_ids' => $tooMany]))
        ->assertSessionHasErrors('category_ids');
});

it('refuses a negeri that is not one of the fourteen', function () {
    $this->actingAs($this->vendor->user)
        ->put(route('vendor.profile.update'), profileWith($this->vendor, ['service_states' => ['Singapura']]))
        ->assertSessionHasErrors('service_states.0');

    expect($this->vendor->fresh()->serviceStates())->toBe(['Selangor']);
});

it('hands the form what is already ticked', function () {
    $this->vendor->categories()->attach($this->catering);
    $this->vendor->update(['service_states' => ['Johor']]);

    // The form is a Vue island, so what it opens with is in its props.
    $this->actingAs($this->vendor->user)
        ->get(route('vendor.profile.edit'))
        ->assertOk()
        ->assertSee('&quot;category_ids&quot;:'.e(json_encode($this->vendor->categories()->pluck('categories.id'))), false)
        ->assertSee('&quot;service_states&quot;:[&quot;Selangor&quot;,&quot;Johor&quot;]', false)
        ->assertSee('&quot;maxCategories&quot;:'.UpdateVendorProfileRequest::MAX_CATEGORIES, false);
});
