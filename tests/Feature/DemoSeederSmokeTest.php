<?php

use App\Models\Vendor;
use App\Models\WeddingGuest;
use App\Models\WeddingSite;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoSeeder;
use Database\Seeders\SiteTemplateSeeder;

it('seeds a demo world whose numbers are all measured from real rows', function () {
    $this->seed(CategorySeeder::class);
    $this->seed(SiteTemplateSeeder::class);
    $this->seed(DemoSeeder::class);

    $vendor = Vendor::where('name', 'ABC Wedding Photography')->sole();
    $site = WeddingSite::sole();

    expect($vendor->response_rate)->toBe(100)
        ->and($vendor->enquiries()->count())->toBe(20)
        ->and(WeddingGuest::count())->toBe(5)
        ->and($site->confirmedPax())->toBe(6)
        ->and($site->awaitingPax())->toBe(5);
});
