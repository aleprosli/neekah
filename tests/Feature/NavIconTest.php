<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

/**
 * Every sidebar item names one of the line icons in components/nav-icon.blade.php.
 * A name that is not there renders a marked placeholder, which is what this
 * catches — a typo would otherwise ship as a bare circle nobody notices.
 */
it('draws every dashboard sidebar with the line icon set', function (Closure $signIn, string $route) {
    $this->actingAs($signIn())
        ->get(route($route))
        ->assertOk()
        ->assertSee('<svg class="size-4 shrink-0"', false)
        ->assertDontSee('data-icon-missing', false);
})->with([
    'admin' => [fn () => User::factory()->admin()->create(), 'admin.dashboard'],
    'vendor' => [fn () => Vendor::factory()->for(Category::first())->create()->user, 'vendor.dashboard'],
    'couple' => [fn () => User::factory()->create(), 'dashboard'],
]);
