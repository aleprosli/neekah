<?php

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use App\Models\WeddingRsvp;
use App\Models\WeddingSite;
use Database\Seeders\SiteTemplateSeeder;

beforeEach(function () {
    $this->seed(SiteTemplateSeeder::class);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create();
    $this->site = WeddingSite::factory()->published()->create(['wedding_id' => $this->wedding->id]);
});

function openCard(WeddingSite $site, string $query = ''): void
{
    test()->get('http://'.$site->subdomain.'.'.config('neekah.site_domain').'/'.$query);
}

it('counts each opening once on the card and once on the day', function () {
    openCard($this->site);
    openCard($this->site);

    $props = $this->actingAs($this->aina)->get(route('site.insights'))->assertOk()->viewData('props');

    expect($props['totals']['views'])->toBe(2)
        ->and(collect($props['daily'])->last()['views'])->toBe(2)
        ->and($props['daily'])->toHaveCount(28);
});

it('counts an opening from a personal link apart from a forwarded one', function () {
    $guest = WeddingGuest::factory()->for($this->wedding)->create();

    openCard($this->site, '?u='.$guest->token);
    openCard($this->site);

    $props = $this->actingAs($this->aina)->get(route('site.insights'))->assertOk()->viewData('props');

    expect($props['totals']['views'])->toBe(2)
        ->and($props['totals']['guest_views'])->toBe(1)
        ->and($props['totals']['opened'])->toBe(1)
        ->and($props['totals']['invited'])->toBe(1);
});

it('leaves a link-preview crawler out of the counters', function () {
    $this->withHeader('User-Agent', 'WhatsApp/2.23 A')
        ->get('http://'.$this->site->subdomain.'.'.config('neekah.site_domain').'/')
        ->assertOk();

    expect($this->site->fresh()->views)->toBe(0)
        ->and($this->site->dailyViews()->count())->toBe(0);
});

it('shows the gap between who was invited and who has replied', function () {
    WeddingGuest::factory()->count(3)->for($this->wedding)->create(['pax_invited' => 2]);
    WeddingRsvp::factory()->create([
        'wedding_site_id' => $this->site->id,
        'attending' => true,
        'pax' => 2,
        'counted' => true,
        'message' => 'Barakallah!',
        'message_approved_at' => now(),
    ]);
    WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'attending' => false, 'pax' => 0]);

    $props = $this->actingAs($this->aina)->get(route('site.insights'))->assertOk()->viewData('props');

    expect($props['totals']['replies'])->toBe(2)
        ->and($props['totals']['attending'])->toBe(1)
        ->and($props['totals']['declined'])->toBe(1)
        ->and($props['totals']['confirmed_pax'])->toBe(2)
        ->and($props['totals']['awaiting_pax'])->toBe(6)
        ->and($props['totals']['wishes'])->toBe(1);
});

it('answers 404 for a couple whose card is still a draft, and redirects one with no wedding', function () {
    $this->site->delete();

    $this->actingAs($this->aina)->get(route('site.insights'))->assertNotFound();

    $this->actingAs(User::factory()->create())->get(route('site.insights'))->assertRedirect(route('weddings.create'));
});
