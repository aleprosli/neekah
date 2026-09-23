<?php

use App\Models\Category;
use App\Models\Vendor;
use App\Support\ProSettings;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    [$this->photo, $this->catering] = Category::query()->orderBy('id')->take(2)->get()->all();
});

it('shows Pro vendors who match the search in a labelled row above the list', function () {
    Vendor::factory()->pro()->for($this->photo)->create(['name' => 'Studio Pro Foto']);
    Vendor::factory()->pro()->for($this->catering)->create(['name' => 'Katering Pro']);
    Vendor::factory()->for($this->photo)->create(['name' => 'Studio Biasa']);

    $this->get(route('vendors.index', ['category' => $this->photo->slug]))
        ->assertOk()
        ->assertSeeInOrder([__('marketplace.sponsored.heading'), 'Studio Pro Foto', 'Studio Biasa'])
        ->assertDontSee('Katering Pro');
});

it('leaves the ordinary list in score order, whoever paid', function () {
    Vendor::factory()->pro()->for($this->photo)->create(['name' => 'Pro Rendah', 'score' => 10]);
    Vendor::factory()->for($this->photo)->create(['name' => 'Earned Tinggi', 'score' => 90]);
    Vendor::factory()->for($this->photo)->create(['name' => 'Earned Sederhana', 'score' => 50]);

    app(ProSettings::class)->save(['sponsored_slots' => 0]);

    $this->get(route('vendors.index'))
        ->assertSeeInOrder(['Earned Tinggi', 'Earned Sederhana', 'Pro Rendah'])
        ->assertDontSee(__('marketplace.sponsored.note'));
});

it('shows no sponsored row when the visitor sorts the list themselves or pages on', function (array $query) {
    Vendor::factory()->pro()->for($this->photo)->create();

    $this->get(route('vendors.index', $query))->assertDontSee(__('marketplace.sponsored.note'));
})->with([
    'cheapest first' => [['sort' => 'price_asc']],
    'second page' => [['page' => 2]],
]);

it('drops a vendor whose Pro has run out', function () {
    Vendor::factory()->for($this->photo)->create(['name' => 'Pro Lama', 'pro_until' => now()->subDay()]);

    $this->get(route('vendors.index'))
        ->assertSee('Pro Lama')
        ->assertDontSee(__('marketplace.sponsored.note'));
});

it('puts the Pro badge on a Pro vendor card and profile only', function () {
    // Different categories, so neither turns up in the other's "related" row.
    $pro = Vendor::factory()->pro()->for($this->photo)->create();
    $free = Vendor::factory()->for($this->catering)->create();

    $this->get(route('vendors.show', $pro))->assertSee(__('marketplace.card.pro_title'));
    $this->get(route('vendors.show', $free))->assertDontSee(__('marketplace.card.pro_title'));
});
