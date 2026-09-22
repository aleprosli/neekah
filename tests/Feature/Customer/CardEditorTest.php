<?php

use App\Models\CardMusicTrack;
use App\Models\SiteTemplate;
use App\Models\User;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Database\Seeders\SiteTemplateSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->seed(SiteTemplateSeeder::class);
    $this->aina = User::factory()->create();
    $this->wedding = Wedding::factory()->for($this->aina)->create(['title' => 'Aina & Hakim']);
});

/**
 * The values a save needs to pass validation, so each test only says what it is about.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function cardSave(array $overrides = []): array
{
    return [
        'subdomain' => 'ainahakim',
        'template' => 'rose-garden',
        'bride_name' => 'Aina Zulkifli',
        'groom_name' => 'Hakim Ismail',
        'event_date' => now()->addMonths(4)->toDateString(),
        ...$overrides,
    ];
}

it('sends the editor the chosen design and the names of the rest, not fifty sets of layers', function () {
    WeddingSite::factory()->create(['wedding_id' => $this->wedding->id, 'template' => 'emerald-estate']);

    $props = $this->actingAs($this->aina)->get(route('site.edit'))->assertOk()->viewData('props');

    expect($props['design']['slug'])->toBe('emerald-estate')
        ->and($props['design']['canvases'])->toHaveCount(3)
        ->and($props['designIndex']['designs'])->toHaveCount(SiteTemplate::active()->count())
        // The index is names and swatches; layers would be about a megabyte of JSON.
        ->and($props['designIndex']['designs'][0])->not->toHaveKey('canvases')
        ->and($props['designIndex']['categories'])->toContain('Traditional');
});

it('answers one design with its canvases and a category with its covers', function () {
    WeddingSite::factory()->create(['wedding_id' => $this->wedding->id]);

    $this->actingAs($this->aina)
        ->getJson(route('site.designs', ['slug' => 'midnight-luxury']))
        ->assertOk()
        ->assertJsonPath('design.slug', 'midnight-luxury')
        ->assertJsonCount(3, 'design.canvases');

    $islamic = $this->actingAs($this->aina)
        ->getJson(route('site.designs', ['category' => 'Islamic']))
        ->assertOk()
        ->json('designs');

    expect(collect($islamic)->pluck('slug'))->toContain('islamic-gold')
        ->and(collect($islamic)->pluck('slug'))->not->toContain('midnight-luxury')
        // A thumbnail is the cover alone, with no sections under it.
        ->and($islamic[0]['card']['canvases'])->toHaveCount(1)
        ->and($islamic[0]['card']['widgets'])->toBe([]);
});

it('refuses the designs of a wedding that is not yours', function () {
    WeddingSite::factory()->create(['wedding_id' => $this->wedding->id]);

    $this->actingAs(User::factory()->create())
        ->get(route('site.designs'))
        ->assertRedirect(route('weddings.create'));
});

it('saves only the colours and faces the couple changed', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave([
            'palette' => ['acc' => '#AABBCC'],
            'fonts' => ['d' => 'Cinzel'],
        ]))
        ->assertRedirect(route('site.edit'));

    $site = WeddingSite::sole();

    // Everything they left alone follows the design, so re-tuning a design later
    // still reaches their card.
    expect($site->palette)->toBe(['acc' => '#aabbcc'])
        ->and($site->fonts)->toBe(['d' => 'Cinzel']);
});

it('refuses a colour that is not a colour and a face we do not ship', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave([
            'palette' => ['bg' => 'not-a-colour'],
            'fonts' => ['s' => 'Comic Sans MS'],
        ]))
        ->assertSessionHasErrors(['palette.bg', 'fonts.s']);

    expect(WeddingSite::count())->toBe(0);
});

it('stores a photo in the slot the design asked for and mirrors the cover for link previews', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave([
            'photos' => [
                'couple_image' => UploadedFile::fake()->image('berdua.jpg'),
                'cover_image' => UploadedFile::fake()->image('kulit.jpg'),
            ],
        ]))
        ->assertRedirect();

    $site = WeddingSite::sole();

    expect($site->slot_images)->toHaveKeys(['couple_image', 'cover_image'])
        // The link-preview image is painted from the column, not from the slots.
        ->and($site->cover_image)->toBe($site->slot_images['cover_image']);

    Storage::disk('public')->assertExists($site->slot_images['couple_image']);
});

it('removes a slot photo, its file and the link preview that used it', function () {
    $this->actingAs($this->aina)->put(route('weddings.site.update', $this->wedding), cardSave([
        'photos' => ['cover_image' => UploadedFile::fake()->image('kulit.jpg')],
    ]));

    $stored = WeddingSite::sole()->slot_images['cover_image'];

    $this->actingAs($this->aina)->put(route('weddings.site.update', $this->wedding), cardSave([
        'remove_photos' => ['cover_image'],
    ]));

    $site = WeddingSite::sole();

    expect($site->slot_images)->toBe([])
        ->and($site->cover_image)->toBeNull();

    Storage::disk('public')->assertMissing($stored);
});

it('keeps the photos a couple uploaded when they switch to another design', function () {
    $this->actingAs($this->aina)->put(route('weddings.site.update', $this->wedding), cardSave([
        'photos' => ['couple_image' => UploadedFile::fake()->image('berdua.jpg')],
    ]));

    $stored = WeddingSite::sole()->slot_images['couple_image'];

    $this->actingAs($this->aina)->put(route('weddings.site.update', $this->wedding), cardSave([
        'template' => 'midnight-luxury',
    ]));

    expect(WeddingSite::sole()->slot_images['couple_image'])->toBe($stored);
});

it('saves the sections in the order they were arranged, once each', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave([
            'widgets' => ['rsvp', 'location', 'rsvp'],
        ]))
        ->assertRedirect();

    expect(WeddingSite::sole()->widgetKeys())->toBe(['rsvp', 'location']);
});

it('refuses a section that is not one of the card sections', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave(['widgets' => ['location', 'nonsense']]))
        ->assertSessionHasErrors('widgets.1');

    expect(WeddingSite::count())->toBe(0);
});

it('plays only a track from the library, and only when music is switched on', function () {
    $track = CardMusicTrack::factory()->create();
    $retired = CardMusicTrack::factory()->create(['is_active' => false]);

    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave([
            'music_enabled' => 1,
            'music_track_id' => $retired->id,
        ]))
        ->assertSessionHasErrors('music_track_id');

    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave([
            'music_enabled' => 1,
            'music_track_id' => $track->id,
        ]))
        ->assertRedirect();

    $site = WeddingSite::sole();

    expect($site->music_enabled)->toBeTrue()
        ->and($site->music_track_id)->toBe($track->id);
});

it('splits the parents into their own lines, which is how the designs print them', function () {
    $this->actingAs($this->aina)
        ->put(route('weddings.site.update', $this->wedding), cardSave([
            'groom_father' => 'Ismail bin Yusof',
            'groom_mother' => 'Salmah binti Osman',
            'bride_short' => 'Aina',
        ]))
        ->assertRedirect();

    $site = WeddingSite::sole();

    expect($site->groom_father)->toBe('Ismail bin Yusof')
        ->and($site->groom_mother)->toBe('Salmah binti Osman')
        ->and($site->shortName('bride'))->toBe('Aina');
});
