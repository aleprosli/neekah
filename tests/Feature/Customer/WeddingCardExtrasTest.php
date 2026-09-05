<?php

use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingRsvp;
use App\Models\WeddingSite;
use App\Models\WeddingSitePhoto;
use Database\Seeders\SiteTemplateSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(SiteTemplateSeeder::class);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create(['title' => 'Aina & Hakim']);
    $this->site = WeddingSite::factory()->published()->create(['wedding_id' => $this->wedding->id]);
});

function cardUrl(WeddingSite $site): string
{
    return 'http://'.$site->subdomain.'.'.config('neekah.site_domain').'/';
}

it('saves a duitnow qr and bank accounts, dropping the empty rows', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), [
            'subdomain' => $this->site->subdomain,
            'template' => 'mawar-pagi',
            'bride_name' => 'Aina Zulkifli',
            'groom_name' => 'Hakim Ismail',
            'event_date' => now()->addMonths(4)->toDateString(),
            'rsvp_enabled' => 1,
            'gift_enabled' => 1,
            'gift_note' => 'Doa restu sudah memadai.',
            'gift_qr_image' => UploadedFile::fake()->image('duitnow.png'),
            'gift_accounts' => [
                ['bank' => 'Maybank', 'holder' => 'Aina Zulkifli', 'number' => '112233445566'],
                ['bank' => '', 'holder' => '', 'number' => ''],
            ],
        ])->assertRedirect();

    $site = $this->wedding->site->fresh();

    expect($site->gift_enabled)->toBeTrue()
        ->and($site->gift_accounts)->toHaveCount(1)
        ->and($site->gift_accounts[0]['bank'])->toBe('Maybank')
        ->and($site->gift_qr_image)->not->toBeNull()
        ->and($site->showsGift())->toBeTrue();

    Storage::disk('public')->assertExists($site->gift_qr_image);
});

it('shows the gift section on the card only when it is switched on', function () {
    $this->site->update(['gift_enabled' => false, 'gift_accounts' => [['bank' => 'Maybank', 'holder' => 'Aina', 'number' => '112233445566']]]);

    $this->get(cardUrl($this->site))->assertOk()->assertDontSee('Salam kaut');

    $this->site->update(['gift_enabled' => true]);

    $this->get(cardUrl($this->site))
        ->assertOk()
        ->assertSee('Salam kaut')
        ->assertSee('112233445566')
        ->assertSee('Hadiah');
});

it('keeps a wish off the card until the couple approves it', function () {
    $rsvp = WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'name' => 'Pak Cik Samad', 'message' => 'Semoga berbahagia!']);

    $this->get(cardUrl($this->site))->assertOk()->assertDontSee('Semoga berbahagia!');

    $this->actingAs($this->aina)
        ->put(route('weddings.rsvps.update', [$this->wedding, $rsvp]), ['approve_message' => 1])
        ->assertRedirect();

    $this->get(cardUrl($this->site))
        ->assertOk()
        ->assertSee('Semoga berbahagia!')
        ->assertSee('Pak Cik Samad');
});

it('hides an approved wish again when the couple changes their mind', function () {
    $rsvp = WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'message' => 'Tahniah!', 'message_approved_at' => now()]);

    $this->actingAs($this->aina)
        ->put(route('weddings.rsvps.update', [$this->wedding, $rsvp]), ['approve_message' => 0])
        ->assertRedirect();

    expect($rsvp->fresh()->message_approved_at)->toBeNull();

    $this->get(cardUrl($this->site))->assertOk()->assertDontSee('Tahniah!');
});

it('respects the switch that turns wishes off entirely', function () {
    WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'message' => 'Tahniah!', 'message_approved_at' => now()]);
    $this->site->update(['wishes_enabled' => false]);

    $this->get(cardUrl($this->site))->assertOk()->assertDontSee('Tahniah!');
});

it('uploads gallery photos and shows them on the card', function () {
    $this->actingAs($this->aina)
        ->post(route('weddings.site.photos.store', $this->wedding), [
            'images' => [UploadedFile::fake()->image('satu.jpg'), UploadedFile::fake()->image('dua.jpg')],
            'caption' => 'Sesi pertunangan',
        ])->assertRedirect();

    expect(WeddingSitePhoto::count())->toBe(2);

    $photo = WeddingSitePhoto::first();
    Storage::disk('public')->assertExists($photo->path);

    $this->get(cardUrl($this->site))->assertOk()->assertSee('Galeri')->assertSee('Sesi pertunangan');
});

it('deletes a gallery photo and its file', function () {
    $this->actingAs($this->aina)->post(route('weddings.site.photos.store', $this->wedding), [
        'images' => [UploadedFile::fake()->image('satu.jpg')],
    ]);

    $photo = WeddingSitePhoto::sole();

    $this->actingAs($this->aina)
        ->delete(route('weddings.site.photos.destroy', [$this->wedding, $photo]))
        ->assertRedirect();

    expect(WeddingSitePhoto::count())->toBe(0);
    Storage::disk('public')->assertMissing($photo->path);
});

it('renders the extras across a light and a dark design', function () {
    $this->site->update([
        'gift_enabled' => true,
        'gift_accounts' => [['bank' => 'CIMB', 'holder' => 'Aina', 'number' => '778899001122']],
    ]);
    WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'message' => 'Barakallah!', 'message_approved_at' => now()]);
    WeddingSitePhoto::factory()->create(['wedding_site_id' => $this->site->id, 'caption' => 'Prewedding']);

    foreach (['mawar-pagi', 'malam-emas'] as $design) {
        $this->site->update(['template' => $design]);

        $this->get(cardUrl($this->site))
            ->assertOk()
            ->assertSee('778899001122')
            ->assertSee('Barakallah!')
            ->assertSee('Prewedding');
    }
});

it('keeps a stranger out of the gallery and the wishes', function () {
    $stranger = User::factory()->create();
    $rsvp = WeddingRsvp::factory()->create(['wedding_site_id' => $this->site->id, 'message' => 'Tahniah!']);

    $this->actingAs($stranger)->post(route('weddings.site.photos.store', $this->wedding), [
        'images' => [UploadedFile::fake()->image('satu.jpg')],
    ])->assertForbidden();

    $this->actingAs($stranger)->put(route('weddings.rsvps.update', [$this->wedding, $rsvp]), ['approve_message' => 1])->assertForbidden();
});
