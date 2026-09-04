<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

it('lets only an admin open the log viewer', function () {
    $this->seed(CategorySeeder::class);
    $admin = User::factory()->admin()->create();
    $customer = User::factory()->create();
    $vendor = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($admin)->get(route('log-viewer.index'))->assertOk();
    $this->actingAs($customer)->get(route('log-viewer.index'))->assertForbidden();
    $this->actingAs($vendor->user)->get(route('log-viewer.index'))->assertForbidden();
    $this->get(route('log-viewer.index'))->assertForbidden();
});

it('links the log viewer from the admin navigation', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Log sistem')
        ->assertSee(route('log-viewer.index'), false);
});
