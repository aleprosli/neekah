<?php

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->aina = User::factory()->create();
});

it('counts down to the wedding in the sidebar and beside the dashboard title', function () {
    $this->travelTo(now()->setDate(2026, 9, 23)->setTime(10, 0));
    $wedding = Wedding::factory()->for($this->aina)->create(['event_date' => '2026-12-20']);
    WeddingSite::factory()->for($wedding)->create(['starts_at' => '11:00']);

    $page = $this->actingAs($this->aina)->get(route('dashboard'))->assertOk();

    expect(substr_count($page->getContent(), 'data-vue="wedding-countdown"'))->toBe(2)
        ->and($page->getContent())->toContain(e(json_encode($wedding->fresh()->startsAt()->toIso8601String())))
        ->and($wedding->fresh()->startsAt()->format('Y-m-d H:i'))->toBe('2026-12-20 11:00');

    $this->actingAs($this->aina)->get(route('checklist.index'))
        ->assertOk()
        ->assertSee('data-vue="wedding-countdown"', false);
});

it('stops counting once the wedding has passed', function () {
    Wedding::factory()->for($this->aina)->create(['event_date' => now()->subWeek()->toDateString()]);

    $this->actingAs($this->aina)->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('data-vue="wedding-countdown"', false);
});
