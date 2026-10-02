<?php

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Database\Seeders\CategorySeeder;
use Illuminate\Testing\TestResponse;

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

it('keeps the countdown after the wedding, where it counts the days married', function () {
    // It used to disappear the day after; a couple then had nothing in its place.
    Wedding::factory()->for($this->aina)->create(['event_date' => now()->subWeek()->toDateString()]);

    $page = $this->actingAs($this->aina)->get(route('dashboard'))->assertOk();

    expect(substr_count($page->getContent(), 'data-vue="wedding-countdown"'))->toBe(2);
});

it('asks a couple without a wedding for their date, in the sidebar', function () {
    $props = countdownProps($this->actingAs($this->aina)->get(route('dashboard'))->assertOk());

    expect($props)->toHaveCount(1)
        ->and($props[0]['target'])->toBeNull()
        ->and($props[0]['createUrl'])->toBe(route('weddings.create'))
        ->and($props[0]['variant'])->toBe('sidebar');
});

it('fills the planning bar from the checklist, not from the calendar', function () {
    $wedding = Wedding::factory()->for($this->aina)->create(['event_date' => now()->addMonths(3)->toDateString()]);
    $wedding->tasks()->delete();
    $wedding->tasks()->createMany([
        ['title' => 'Tempah dewan', 'completed_at' => now()],
        ['title' => 'Tempah katering', 'completed_at' => now()],
        ['title' => 'Cetak kad'],
        ['title' => 'Pilih baju'],
    ]);

    expect($wedding->planningProgress())->toBe(50);

    // Visiting /checklist adds the master list to the wedding, so read the
    // dashboard, where the hero and the sidebar both carry the figure.
    $props = countdownProps($this->actingAs($this->aina)->get(route('dashboard')));

    expect(array_column($props, 'progress'))->toBe([50, 50]);
});

/**
 * The props of every countdown island on a page.
 *
 * @return list<array<string, mixed>>
 */
function countdownProps(TestResponse $response): array
{
    preg_match_all('/data-vue="wedding-countdown" data-props="([^"]*)"/', $response->getContent(), $matches);

    return array_map(fn (string $props): array => json_decode(html_entity_decode($props, ENT_QUOTES), true), $matches[1]);
}
