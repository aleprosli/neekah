<?php

use App\Models\WeddingRsvp;
use App\Models\WeddingSite;

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
        'template' => 'klasik',
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

it('renders every template', function () {
    foreach (array_keys(WeddingSite::TEMPLATES) as $template) {
        $site = WeddingSite::factory()->published()->create(['template' => $template]);

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

it('seals the card behind an opening gate with motion and a live countdown', function () {
    $site = WeddingSite::factory()->published()->create(['bride_name' => 'Aina', 'groom_name' => 'Hakim', 'starts_at' => '11:00']);

    $response = $this->get(siteUrl($site));

    $response->assertOk()
        ->assertSee('data-gate', false)
        ->assertSee('Buka kad')
        ->assertSee('data-countdown', false)
        ->assertSee('data-reveal', false)
        ->assertSee('nk-petal', false)
        ->assertSee('data-unit="seconds"', false);

    // The countdown targets the ceremony start, not midnight.
    $response->assertSee($site->event_date->copy()->setTimeFromTimeString('11:00:00')->toIso8601String(), false);
});

it('opens the card straight away in preview, with no gate to click through', function () {
    $this->get(route('sites.templates.show', 'bunga'))
        ->assertOk()
        ->assertSee('data-card', false)
        ->assertDontSee('data-gate', false);
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

it('shows the template gallery and a sample of each design', function () {
    $this->get(route('sites.templates'))->assertOk()->assertSee('Pilih template anda')->assertSee('Klasik');

    foreach (array_keys(WeddingSite::TEMPLATES) as $template) {
        $this->get(route('sites.templates.show', $template))
            ->assertOk()
            ->assertSee('Contoh template')
            ->assertSee('Aina Zulkifli');
    }

    $this->get(route('sites.templates.show', 'tiada'))->assertNotFound();
});
