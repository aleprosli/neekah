<?php

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SiteTemplateSeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->seed(SiteTemplateSeeder::class);
});

it('walks a new couple into making their card, starting with the template', function () {
    $couple = User::factory()->create();
    Wedding::factory()->for($couple)->create();

    $this->actingAs($couple)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Cipta kad dalam 4 langkah')
        ->assertSee('0 daripada 4 langkah selesai.')
        ->assertSee('href="'.route('site.edit').'#template"', false);
});

it('moves the couple on to the details once the card is saved without a venue address', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    WeddingSite::factory()->for($wedding)->create(['venue_address' => null]);

    $this->actingAs($couple)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('1 daripada 4 langkah selesai.')
        ->assertSee('href="'.route('site.edit').'#majlis"', false);
});

it('asks for publishing once the details are in, then for sharing once it is out', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    $site = WeddingSite::factory()->for($wedding)->create([
        'venue_address' => 'Jalan Sultanah, Alor Setar',
        'itinerary' => [['time' => '11:00 pagi', 'label' => 'Ketibaan tetamu']],
    ]);

    $this->actingAs($couple)->get(route('dashboard'))
        ->assertSee('2 daripada 4 langkah selesai.')
        ->assertSee('Pratonton &amp; siarkan', false);

    $site->update(['is_published' => true]);

    $this->actingAs($couple)->get(route('dashboard'))
        ->assertSee('3 daripada 4 langkah selesai.')
        ->assertSee('Buka senarai tetamu');
});

it('stops guiding once guests have opened the published card', function () {
    $couple = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    WeddingSite::factory()->published()->for($wedding)->create([
        'venue_address' => 'Jalan Sultanah, Alor Setar',
        'itinerary' => [['time' => '11:00 pagi', 'label' => 'Ketibaan tetamu']],
        'views' => 3,
    ]);

    $this->actingAs($couple)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Panduan kad digital');
});

it('shows where the couple is in the editor too', function () {
    $couple = User::factory()->create();
    Wedding::factory()->for($couple)->create();

    $this->actingAs($couple)
        ->get(route('site.edit'))
        ->assertOk()
        ->assertSee('Panduan kad digital')
        ->assertSee('0 daripada 4 langkah selesai.');
});
