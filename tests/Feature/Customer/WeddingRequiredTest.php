<?php

use App\Models\User;
use App\Models\Wedding;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SiteTemplateSeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->seed(SiteTemplateSeeder::class);
});

$planningTools = ['checklist.index', 'guests.index', 'timeline.index', 'budget.index', 'site.edit', 'site.preview', 'site.subdomain', 'camera.index'];

it('sends a couple without a wedding to the create form instead of a 404', function (string $name) {
    $this->actingAs(User::factory()->create())
        ->get(route($name))
        ->assertRedirect(route('weddings.create'))
        ->assertSessionHas('status');
})->with($planningTools);

it('lets a couple who has a wedding through', function (string $name) {
    $aina = User::factory()->create();
    Wedding::factory()->for($aina)->create();

    $this->actingAs($aina)
        ->get(route($name))
        ->assertOk();
})->with($planningTools);
