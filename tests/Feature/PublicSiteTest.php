<?php

use App\Models\SiteTemplate;
use App\Models\WeddingGuest;
use App\Models\WeddingRsvp;
use App\Models\WeddingSite;
use Database\Seeders\SiteTemplateSeeder;

beforeEach(function () {
    $this->seed(SiteTemplateSeeder::class);
});

function siteUrl(WeddingSite $site, string $path = '/'): string
{
    return 'http://'.$site->subdomain.'.'.config('neekah.site_domain').$path;
}

it('serves a published invitation on its own subdomain', function () {
    $site = WeddingSite::factory()->published()->create([
        'subdomain' => 'ainapilihhakim',
        'bride_name' => 'Aina',
        'groom_name' => 'Hakim',
        'venue_name' => 'Dewan Seri Melati',
        'template' => 'seri-gangsa',
    ]);

    $this->get(siteUrl($site))
        ->assertOk()
        ->assertSee('Aina')
        ->assertSee('Hakim')
        ->assertSee('Dewan Seri Melati')
        ->assertSee('Sahkan kehadiran anda');
});

it('counts a view each time the invitation is opened', function () {
    $site = WeddingSite::factory()->published()->create();

    $this->get(siteUrl($site));
    $this->get(siteUrl($site));

    expect($site->fresh()->views)->toBe(2);
});

it('returns 404 for an unpublished or unknown address', function () {
    $draft = WeddingSite::factory()->create(['subdomain' => 'belum-siar']);

    $this->get(siteUrl($draft))->assertNotFound();
    $this->get('http://tiada-langsung.'.config('neekah.site_domain'))->assertNotFound();
});

it('renders all fifty designs', function () {
    $templates = SiteTemplate::active()->get();

    expect($templates)->toHaveCount(50);

    foreach ($templates as $template) {
        $site = WeddingSite::factory()->published()->create(['template' => $template->slug]);

        $this->get(siteUrl($site))->assertOk()->assertSee($site->bride_name);
    }
});

it('records an RSVP from a guest', function () {
    $site = WeddingSite::factory()->published()->create();

    $this->post(siteUrl($site, '/rsvp'), [
        'name' => 'Pak Cik Samad',
        'phone' => '012-999 8888',
        'attending' => 1,
        'pax' => 4,
        'message' => 'Semoga berbahagia!',
    ])->assertRedirect();

    $rsvp = WeddingRsvp::sole();

    expect($rsvp->name)->toBe('Pak Cik Samad')
        ->and($rsvp->attending)->toBeTrue()
        ->and($rsvp->pax)->toBe(4);
});

it('answers the card in the background with a thank-you rather than a redirect', function () {
    $site = WeddingSite::factory()->published()->create();

    // A redirect would make the browser reload the card the guest just opened.
    $this->postJson(siteUrl($site, '/rsvp'), ['name' => 'Pak Cik Samad', 'attending' => 1, 'pax' => 2])
        ->assertOk()
        ->assertJsonPath('message', __('props.couple.terima_kasih_kehadiran'));

    $this->postJson(siteUrl($site, '/rsvp'), ['attending' => 1, 'pax' => 2])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('name');

    expect(WeddingRsvp::count())->toBe(1);
});

it('stores a decline with zero pax', function () {
    $site = WeddingSite::factory()->published()->create();

    $this->post(siteUrl($site, '/rsvp'), ['name' => 'Kawan Lama', 'attending' => 0])->assertRedirect();

    expect(WeddingRsvp::sole()->pax)->toBe(0);
});

it('refuses an RSVP after the deadline or without a name', function () {
    $closed = WeddingSite::factory()->published()->create(['rsvp_deadline' => now()->subDay()->toDateString()]);
    $this->post(siteUrl($closed, '/rsvp'), ['name' => 'Terlambat', 'attending' => 1, 'pax' => 1])->assertForbidden();

    $open = WeddingSite::factory()->published()->create();
    $this->post(siteUrl($open, '/rsvp'), ['attending' => 1, 'pax' => 1])->assertSessionHasErrors('name');

    expect(WeddingRsvp::count())->toBe(0);
});

it('seals the card behind an opening gate with a live countdown', function () {
    $site = WeddingSite::factory()->published()->create(['template' => 'neekah-signature', 'bride_name' => 'Aina', 'groom_name' => 'Hakim', 'starts_at' => '11:00']);

    $response = $this->get(siteUrl($site));

    // The card is one Vue island; what it draws is in the props it is handed.
    $response->assertOk()
        ->assertSee('data-vue="card-view"', false)
        ->assertSee('Buka kad')
        ->assertSee('&quot;countdown&quot;', false);

    // The countdown targets the ceremony start, not midnight.
    $props = cardProps($response);

    expect($props['gate']['enabled'])->toBeTrue()
        ->and(collect($props['widgets'])->firstWhere('key', 'countdown')['target'])
        ->toBe($site->event_date->copy()->setTimeFromTimeString('11:00:00')->toIso8601String());
});

it('opens the traditional designs with the Bismillah and leaves the modern ones without', function () {
    $traditional = WeddingSite::factory()->published()->create(['template' => 'islamic-gold']);
    $modern = WeddingSite::factory()->published()->create(['template' => 'black-gold']);

    expect(SiteTemplate::where('slug', 'islamic-gold')->sole()->showsBismillah())->toBeTrue()
        ->and(SiteTemplate::where('slug', 'black-gold')->sole()->showsBismillah())->toBeFalse();

    // The Bismillah is a layer in the artwork, so it travels in the card's props.
    $this->get(siteUrl($traditional))->assertOk()->assertSee('Bismillah', false);
    $this->get(siteUrl($modern))->assertOk()->assertDontSee('Bismillah', false);
});

it('carries only the sections this card can answer', function () {
    $site = WeddingSite::factory()->published()->create([
        'map_url' => 'https://maps.google.com/?q=dewan',
        'contacts' => [['name' => 'Puan Rohana', 'phone' => '012-345 6789']],
        'rsvp_enabled' => true,
        'gift_enabled' => false,
        'wishes_enabled' => false,
    ]);

    $widgets = collect(cardProps($this->get(siteUrl($site))->assertOk())['widgets'])->pluck('key');

    expect($widgets)->toContain('location', 'contacts', 'rsvp')
        ->and($widgets)->not->toContain('gift')
        ->and($widgets)->not->toContain('wishes')
        // Nothing was uploaded and no tentatif was written, so neither is drawn.
        ->and($widgets)->not->toContain('gallery')
        ->and($widgets)->not->toContain('itinerary');
});

it('hands the browser the design the couple chose, not the one before it', function () {
    $site = WeddingSite::factory()->published()->create(['template' => 'black-gold']);

    $props = cardProps($this->get(siteUrl($site))->assertOk());

    expect($props['design']['slug'])->toBe('black-gold')
        ->and($props['canvases'])->toHaveCount(3)
        ->and(collect($props['canvases'])->pluck('key')->all())->toBe(['cover', 'invitation', 'event'])
        // The palette reaches the page as CSS variables, which is what lets a
        // colour change repaint the card without recomposing a single layer.
        ->and($props['vars']['--c-bg'])->toBe('#0a0a0a');
});

it('opens the card straight away in preview, with no gate to click through', function () {
    $response = $this->get(route('sites.templates.show', 'rose-garden'))->assertOk();

    expect(cardProps($response)['gate']['enabled'])->toBeFalse();
});

it('offers a calendar file guests can add to their phone', function () {
    $site = WeddingSite::factory()->published()->create([
        'bride_name' => 'Aina',
        'groom_name' => 'Hakim',
        'venue_name' => 'Dewan Seri Melati',
        'starts_at' => '11:00',
        'ends_at' => '16:00',
    ]);

    $response = $this->get(siteUrl($site, '/kalendar.ics'));

    $response->assertOk()
        ->assertHeader('content-type', 'text/calendar; charset=utf-8')
        ->assertSee('BEGIN:VEVENT', false)
        ->assertSee('Majlis Perkahwinan Aina & Hakim', false)
        ->assertSee('Dewan Seri Melati', false);

    $this->get('http://tiada.'.config('neekah.site_domain').'/kalendar.ics')->assertNotFound();
});

it('shows the gallery, filters it by category, and samples every design', function () {
    $this->get(route('sites.templates'))
        ->assertOk()
        ->assertSee('50 template untuk dipilih')
        ->assertSee('Royal Songket Gold')
        ->assertSee('Midnight Luxury');

    $this->get(route('sites.templates', ['category' => 'Islamic']))
        ->assertOk()
        ->assertSee('Islamic Gold')
        ->assertDontSee('Midnight Luxury');

    foreach (SiteTemplate::active()->get() as $template) {
        $this->get(route('sites.templates.show', $template))
            ->assertOk()
            ->assertSee('Contoh template '.$template->name)
            ->assertSee('Irdina Maisarah');
    }

    $this->get(route('sites.templates.show', 'tiada'))->assertNotFound();
});

it('leaves the gallery tiles to mount as the visitor scrolls, and the single sample at once', function () {
    // Fifty live card apps mounted on load is what data-vue-lazy exists to avoid.
    $gallery = $this->get(route('sites.templates'))->assertOk();

    expect(substr_count($gallery->getContent(), 'data-vue="card-view" data-vue-lazy'))->toBe(50);

    $this->get(route('sites.templates.show', 'rose-garden'))
        ->assertOk()
        ->assertSee('data-vue="card-view"', false)
        ->assertDontSee('data-vue-lazy', false);
});

it('greets the named guest behind their personal link and records the open', function () {
    $site = WeddingSite::factory()->published()->create();
    $guest = WeddingGuest::factory()->for($site->wedding)->create(['name' => 'Pak Long Rahim', 'pax_invited' => 4]);

    $response = $this->get(siteUrl($site, '/?u='.$guest->token))->assertOk()->assertSee('Pak Long Rahim');

    $props = cardProps($response);

    expect($props['guest']['name'])->toBe('Pak Long Rahim')
        ->and($props['guest']['token'])->toBe($guest->token)
        ->and($props['guest']['pax_invited'])->toBe(4);

    $guest->refresh();

    expect($guest->open_count)->toBe(1)
        ->and($guest->first_opened_at)->not->toBeNull();
});

it('does not count a link preview crawler as the guest opening the card', function () {
    $site = WeddingSite::factory()->published()->create();
    $guest = WeddingGuest::factory()->for($site->wedding)->create();

    $this->withHeader('User-Agent', 'WhatsApp/2.23 A')->get(siteUrl($site, '/?u='.$guest->token))->assertOk();

    expect($guest->fresh()->open_count)->toBe(0);
});

it('renders the ordinary card for a guessed token, giving away nothing', function () {
    $site = WeddingSite::factory()->published()->create();
    $guest = WeddingGuest::factory()->for($site->wedding)->create(['name' => 'Pak Long Rahim']);

    $this->get(siteUrl($site, '/?u=tidakwujudlangsu'))
        ->assertOk()
        ->assertDontSee('Pak Long Rahim')
        ->assertDontSee('Kepada');
});

it('ignores a token belonging to a different wedding', function () {
    $site = WeddingSite::factory()->published()->create();
    $other = WeddingGuest::factory()->create(['name' => 'Tetamu Majlis Lain']);

    $this->get(siteUrl($site, '/?u='.$other->token))
        ->assertOk()
        ->assertDontSee('Tetamu Majlis Lain');
});

it('attaches a tokenised reply to its guest and updates it on a second submission', function () {
    $site = WeddingSite::factory()->published()->create();
    $guest = WeddingGuest::factory()->for($site->wedding)->create(['pax_invited' => 6]);

    $this->post(siteUrl($site, '/rsvp'), ['u' => $guest->token, 'name' => 'Rahim', 'attending' => 1, 'pax' => 2])->assertRedirect();
    $this->post(siteUrl($site, '/rsvp'), ['u' => $guest->token, 'name' => 'Rahim', 'attending' => 1, 'pax' => 4])->assertRedirect();

    expect(WeddingRsvp::count())->toBe(1)
        ->and(WeddingRsvp::sole()->pax)->toBe(4)
        ->and(WeddingRsvp::sole()->wedding_guest_id)->toBe($guest->id)
        ->and(WeddingRsvp::sole()->matched_by)->toBe('token')
        ->and($site->confirmedPax())->toBe(4);
});

it('refuses to seat more people than the invitation allows', function () {
    $site = WeddingSite::factory()->published()->create();
    $guest = WeddingGuest::factory()->for($site->wedding)->create(['pax_invited' => 2]);

    $this->post(siteUrl($site, '/rsvp'), ['u' => $guest->token, 'name' => 'Rahim', 'attending' => 1, 'pax' => 20])
        ->assertSessionHasErrors('pax');

    expect(WeddingRsvp::count())->toBe(0);
});

it('matches an untokenised reply on phone only when exactly one guest carries it', function () {
    $site = WeddingSite::factory()->published()->create();
    $sole = WeddingGuest::factory()->for($site->wedding)->create(['phone' => '012-345 6789']);

    $this->post(siteUrl($site, '/rsvp'), ['name' => 'Rahim', 'phone' => '+60123456789', 'attending' => 1, 'pax' => 2]);

    expect(WeddingRsvp::sole()->wedding_guest_id)->toBe($sole->id)
        ->and(WeddingRsvp::sole()->matched_by)->toBe('phone');
});

it('leaves a reply unattached when two guests share a phone number', function () {
    $site = WeddingSite::factory()->published()->create();
    WeddingGuest::factory()->count(2)->for($site->wedding)->create(['phone' => '012-345 6789']);

    $this->post(siteUrl($site, '/rsvp'), ['name' => 'Rahim', 'phone' => '0123456789', 'attending' => 1, 'pax' => 2]);

    expect(WeddingRsvp::sole()->wedding_guest_id)->toBeNull()
        ->and(WeddingRsvp::sole()->matched_by)->toBeNull();
});

it('never matches on name alone', function () {
    $site = WeddingSite::factory()->published()->create();
    WeddingGuest::factory()->for($site->wedding)->create(['name' => 'Aina', 'phone' => null]);

    $this->post(siteUrl($site, '/rsvp'), ['name' => 'Aina', 'attending' => 1, 'pax' => 1]);

    expect(WeddingRsvp::sole()->wedding_guest_id)->toBeNull();
});

it('keeps the guest list off the public card', function () {
    $site = WeddingSite::factory()->published()->create();
    $guest = WeddingGuest::factory()->for($site->wedding)->create(['name' => 'Pak Long Rahim', 'phone' => '012-345 6789']);
    WeddingGuest::factory()->for($site->wedding)->create(['name' => 'Kak Ani Rahmah']);

    $this->get(siteUrl($site, '/?u='.$guest->token))
        ->assertOk()
        ->assertDontSee('Kak Ani Rahmah')
        ->assertDontSee('012-345 6789');
});
