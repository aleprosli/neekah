<?php

use App\Enums\GuestStatus;
use App\Enums\WeddingRole;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use App\Models\WeddingRsvp;
use App\Models\WeddingSite;
use Database\Seeders\SiteTemplateSeeder;

beforeEach(function () {
    $this->seed(SiteTemplateSeeder::class);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create(['title' => 'Aina & Hakim']);
    $this->wedding->addMember($this->aina, WeddingRole::Owner);
    $this->site = WeddingSite::factory()->published()->create(['wedding_id' => $this->wedding->id]);
});

it('adds a guest and gives them a personal link', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.guests.store', $this->wedding), [
            'name' => 'Pak Long Rahim',
            'phone' => '012-345 6789',
            'side' => 'groom',
            'group' => 'family',
            'pax_invited' => 4,
        ])->assertRedirect();

    $guest = WeddingGuest::sole();

    expect($guest->name)->toBe('Pak Long Rahim')
        ->and($guest->pax_invited)->toBe(4)
        ->and($guest->phone_normalised)->toBe('60123456789')
        ->and($guest->token)->toHaveLength(16)
        ->and($guest->inviteUrl())->toContain('?u='.$guest->token);
});

it('lists guests and both headcount numbers separately', function () {
    $replied = WeddingGuest::factory()->for($this->wedding)->create(['name' => 'Kak Ani', 'pax_invited' => 2]);
    WeddingGuest::factory()->for($this->wedding)->create(['name' => 'Abang Mie', 'pax_invited' => 5]);
    WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'wedding_guest_id' => $replied->id, 'pax' => 2]);

    $this->actingAs($this->aina)->get(route('guests.index'))
        ->assertOk()
        ->assertSee('Kak Ani')
        ->assertSee('Abang Mie')
        ->assertSee('2 orang')
        ->assertSee('sehingga 5 orang');
});

it('imports a pasted list and reports the rows it could not use', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.guests.import', $this->wedding), [
            'side' => 'bride',
            'group' => 'other',
            'rows' => "Aina Sofea, 0123456789, bride, family, 2\nPak Long Rahim, , groom, family, 4\n, 0199998888, bride, friends, 1",
        ])->assertRedirect()->assertSessionHas('importErrors');

    expect(WeddingGuest::count())->toBe(2)
        ->and(session('importErrors'))->toContain('Baris 3: nama tetamu kosong.');
});

it('updates rather than duplicates when the same list is pasted again', function () {
    $paste = fn () => $this->actingAs($this->aina)->post(route('weddings.guests.import', $this->wedding), [
        'side' => 'bride', 'group' => 'other', 'rows' => 'Aina Sofea, 0123456789, bride, family, 2',
    ]);

    $paste();
    $paste();

    expect(WeddingGuest::count())->toBe(1);
});

it('records sharing as the couple own action and lets them undo it', function () {
    $guest = WeddingGuest::factory()->for($this->wedding)->create();

    $this->actingAs($this->aina)
        ->post(route('weddings.guests.share', [$this->wedding, $guest]))
        ->assertRedirectContains('wa.me');

    expect($guest->fresh()->shared_at)->not->toBeNull()
        ->and($guest->fresh()->status())->toBe(GuestStatus::Shared);

    $this->actingAs($this->aina)->delete(route('weddings.guests.share.destroy', [$this->wedding, $guest]))->assertRedirect();

    expect($guest->fresh()->shared_at)->toBeNull();
});

it('lets the couple strike a duplicate reply from the headcount without deleting it', function () {
    $rsvp = WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'pax' => 4]);

    expect($this->site->confirmedPax())->toBe(4);

    $this->actingAs($this->aina)
        ->put(route('weddings.rsvps.update', [$this->wedding, $rsvp]), ['counted' => 0])
        ->assertRedirect();

    expect($this->site->confirmedPax())->toBe(0)
        ->and($rsvp->fresh()->message)->toBe($rsvp->message);
});

it('lets the couple detach a phone match that was wrong', function () {
    $guest = WeddingGuest::factory()->for($this->wedding)->create();
    $rsvp = WeddingRsvp::factory()->create([
        'wedding_site_id' => $this->site->id,
        'wedding_guest_id' => $guest->id,
        'matched_by' => 'phone',
    ]);

    $this->actingAs($this->aina)
        ->put(route('weddings.rsvps.update', [$this->wedding, $rsvp]), ['detach' => 1])
        ->assertRedirect();

    expect($rsvp->fresh()->wedding_guest_id)->toBeNull()
        ->and($rsvp->fresh()->matched_by)->toBeNull();
});

it('keeps one couple guest list away from another', function () {
    $stranger = User::factory()->create();
    $guest = WeddingGuest::factory()->for($this->wedding)->create();

    $this->actingAs($stranger)->post(route('weddings.guests.store', $this->wedding), [
        'name' => 'Penyusup', 'side' => 'bride', 'group' => 'other', 'pax_invited' => 1,
    ])->assertForbidden();

    $this->actingAs($stranger)->delete(route('weddings.guests.destroy', [$this->wedding, $guest]))->assertForbidden();
});

it('turns a guest away from the list when signed out', function () {
    $this->get(route('guests.index'))->assertRedirect(route('login'));
});
