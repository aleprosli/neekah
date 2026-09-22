<?php

use App\Models\CardNfcCard;
use App\Models\User;
use App\Models\WeddingSite;
use Database\Seeders\SiteTemplateSeeder;

beforeEach(function () {
    $this->seed(SiteTemplateSeeder::class);
});

it('sends a tap to the invitation the card is pointed at and counts it', function () {
    $site = WeddingSite::factory()->published()->create();
    $card = CardNfcCard::factory()->create(['wedding_site_id' => $site->id]);

    $this->get(route('nfc.tap', $card->uid))->assertRedirect($site->url());

    $card->refresh();

    expect($card->taps)->toBe(1)
        ->and($card->last_tapped_at)->not->toBeNull();
});

it('sends a tap to Neekah when the card has no invitation yet or it is still a draft', function () {
    $blank = CardNfcCard::factory()->create();
    $draft = CardNfcCard::factory()->create(['wedding_site_id' => WeddingSite::factory()->create()->id]);

    // The guest is holding something real, so an error page would read as a broken card.
    $this->get(route('nfc.tap', $blank->uid))->assertRedirect(route('landing'));
    $this->get(route('nfc.tap', $draft->uid))->assertRedirect(route('landing'));

    expect($blank->fresh()->taps)->toBe(1);
});

it('answers 404 for a card that is unknown or out of service', function () {
    $retired = CardNfcCard::factory()->create([
        'wedding_site_id' => WeddingSite::factory()->published()->create()->id,
        'is_active' => false,
    ]);

    $this->get(route('nfc.tap', 'tiadalangsung'))->assertNotFound();
    $this->get(route('nfc.tap', $retired->uid))->assertNotFound();

    expect($retired->fresh()->taps)->toBe(0);
});

it('mints a batch of cards with uids nobody has to squint at', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.card-nfc.store'), ['quantity' => 5, 'label' => 'Kelompok pertama'])
        ->assertRedirect();

    $cards = CardNfcCard::all();

    expect($cards)->toHaveCount(5)
        ->and($cards->pluck('uid')->unique())->toHaveCount(5)
        ->and($cards->pluck('label')->unique()->all())->toBe(['Kelompok pertama']);

    // No 0/O or 1/l: these are read off a card and typed in by hand.
    $cards->each(fn (CardNfcCard $card) => expect($card->uid)->toMatch('/^[a-hj-km-np-z2-9]+$/'));
});

it('points a card at an invitation and takes it out of service again', function () {
    $admin = User::factory()->admin()->create();
    $site = WeddingSite::factory()->published()->create();
    $card = CardNfcCard::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.card-nfc.update', $card), ['wedding_site_id' => $site->id, 'label' => 'Meja 1', 'is_active' => 1])
        ->assertRedirect();

    expect($card->fresh()->site->is($site))->toBeTrue();

    $this->actingAs($admin)->put(route('admin.card-nfc.update', $card), ['wedding_site_id' => $site->id]);

    expect($card->fresh()->is_active)->toBeFalse();
    $this->get(route('nfc.tap', $card->uid))->assertNotFound();
});

it('refuses to delete a card that has already been tapped', function () {
    $admin = User::factory()->admin()->create();
    $tapped = CardNfcCard::factory()->create(['taps' => 3]);
    $fresh = CardNfcCard::factory()->create();

    $this->actingAs($admin)->delete(route('admin.card-nfc.destroy', $tapped))->assertSessionHasErrors('card');
    $this->actingAs($admin)->delete(route('admin.card-nfc.destroy', $fresh))->assertRedirect();

    expect(CardNfcCard::pluck('id')->all())->toBe([$tapped->id]);
});

it('lists the cards with the tap link an admin can copy onto a tag', function () {
    $card = CardNfcCard::factory()->create(['label' => 'Meja utama']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.card-nfc.index'))
        ->assertOk()
        ->assertSee('Meja utama')
        ->assertSee($card->uid)
        ->assertViewHas('props', fn (array $props): bool => $props['cards'][0]['url'] === route('nfc.tap', $card->uid));
});

it('keeps a couple out of the NFC card admin', function () {
    $this->actingAs(User::factory()->create())->get(route('admin.card-nfc.index'))->assertForbidden();
});
