<?php

use App\Models\Booking;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use App\Models\WeddingInvitation;
use App\Support\CoupleNextSteps;
use Database\Seeders\CategorySeeder;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->aina = User::factory()->create();
});

/**
 * The props of the dashboard's Vue page.
 *
 * @return array<string, mixed>
 */
function dashboardProps(TestResponse $response, string $part = 'steps'): array
{
    preg_match('/data-vue="customer-dashboard-page" data-props="([^"]*)"/', $response->getContent(), $matches);

    return json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, flags: JSON_THROW_ON_ERROR);
}

it('shows the next three steps, the card first, and only what is still open', function () {
    $wedding = Wedding::factory()->for($this->aina)->create(['budget' => 0]);

    $props = dashboardProps($this->actingAs($this->aina)->get(route('dashboard'))->assertOk());

    expect(array_column($props['steps'], 'key'))->toBe(['card', 'partner', 'guests'])
        ->and($props['stepsTotal'])->toBe(6);

    WeddingInvitation::factory()->for($wedding)->create(['email' => 'hakim@example.com']);
    WeddingGuest::factory()->for($wedding)->create();

    $props = dashboardProps($this->actingAs($this->aina->fresh())->get(route('dashboard')));

    expect(array_column($props['steps'], 'key'))->toBe(['card', 'checklist', 'vendor'])
        ->and($props['stepsDone'])->toBe(2);
});

it('keeps the dashboard to the countdown, the steps, four shortcuts and the partner card', function () {
    Wedding::factory()->for($this->aina)->create(['budget' => 30000, 'title' => 'Aliff & Izzati']);

    $response = $this->actingAs($this->aina)->get(route('dashboard'))->assertOk();
    $props = dashboardProps($response);

    expect(array_column($props['shortcuts'], 'key'))->toBe(['card', 'guests', 'checklist', 'budget'])
        ->and(collect($props['shortcuts'])->firstWhere('key', 'budget')['value'])->toBe('RM30,000')
        ->and($props)->not->toHaveKeys(['stats', 'categories', 'budget']);

    // The names live in the countdown card; there is no separate page heading.
    $response->assertSee('Aliff &amp; Izzati', false)
        ->assertSee('id="pasangan"', false)
        ->assertDontSee(__('customer.checklist_categories'));
});

it('asks a couple with no wedding to create one, with no sidebar until they do', function () {
    $response = $this->actingAs($this->aina)->get(route('dashboard'))->assertOk();
    $props = dashboardProps($response);

    expect($props['hasWedding'])->toBeFalse()
        ->and($props['createUrl'])->toBe(route('weddings.create'))
        ->and($props)->toHaveKey('checklistCount')
        ->and($props)->not->toHaveKey('steps');

    // No planning tool is offered before there is a date to plan around.
    $response->assertDontSee(route('checklist.index'), false)
        ->assertDontSee(route('guests.index'), false);

    Wedding::factory()->for($this->aina)->create();

    $this->actingAs($this->aina->fresh())->get(route('dashboard'))
        ->assertSee(route('checklist.index'), false);
});

it('points a couple without a wedding at the bookings a vendor already recorded', function () {
    Booking::factory()->for($this->aina)->create(['wedding_id' => null]);

    $props = dashboardProps($this->actingAs($this->aina)->get(route('dashboard')));

    expect($props['bookingsCount'])->toBe(1)
        ->and($props['bookingsUrl'])->toBe(route('bookings.index'));
});

it('marks the invitation card and Neekah Kenangan as premium in the sidebar', function () {
    Wedding::factory()->for($this->aina)->create();

    $this->actingAs($this->aina)->get(route('dashboard'))
        ->assertSeeInOrder([__('pages.sidebar_couple.kad_jemputan'), __('pages.sidebar_couple.premium'), __('pages.sidebar_couple.kamera'), __('pages.sidebar_couple.premium')]);
});

it('counts the steps done from what the couple has actually done', function () {
    $wedding = Wedding::factory()->for($this->aina)->create(['budget' => 20000]);
    $steps = new CoupleNextSteps($wedding);

    expect(collect($steps->all())->firstWhere('key', 'budget')['done'])->toBeTrue()
        ->and(collect($steps->all())->firstWhere('key', 'guests')['done'])->toBeFalse();
});
