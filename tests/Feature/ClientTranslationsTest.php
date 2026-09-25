<?php

use App\Models\Category;
use App\Models\Package;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Testing\TestResponse;

/**
 * The dictionary a page hands its Vue islands.
 *
 * @return array<string, mixed>
 */
function clientDictionary(TestResponse $response): array
{
    preg_match('#<script type="application/json" id="translations">(.*?)</script>#s', $response->getContent(), $matches);

    return json_decode(html_entity_decode($matches[1] ?? '{}'), true) ?? [];
}

it('gives a vendor page taking online bookings the date picker strings', function () {
    $this->seed(CategorySeeder::class);
    enableOnlineBooking();
    $vendor = Vendor::factory()->for(Category::first())->takingOnlineBookings()->create();
    Package::factory()->for($vendor)->create();

    $dictionary = clientDictionary($this->get(route('vendors.show', $vendor))->assertOk());

    expect($dictionary['date_picker']['status_open'] ?? null)->toBe(__('ui.date_picker.status_open'));
});

it('gives the sign-in page the show and hide password labels', function () {
    $dictionary = clientDictionary($this->get(route('login'))->assertOk());

    expect($dictionary['copy']['show_password'] ?? null)->toBe(__('ui.copy.show_password'));
});
