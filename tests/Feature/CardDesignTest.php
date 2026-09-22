<?php

use App\Models\SiteTemplate;
use App\Models\WeddingSite;
use App\Support\Card\Catalog;
use App\Support\Card\Palettes;
use App\Support\Card\SceneComposer;
use Database\Seeders\SiteTemplateSeeder;

beforeEach(function () {
    $this->seed(SiteTemplateSeeder::class);
});

function cardUrlFor(WeddingSite $site): string
{
    return 'http://'.$site->subdomain.'.'.config('neekah.site_domain').'/';
}

it('composes every design in the catalogue into three canvases of real layers', function () {
    $designs = SiteTemplate::active()->get();

    expect($designs)->toHaveCount(count(Catalog::all()));

    foreach ($designs as $design) {
        $canvases = $design->canvases();

        expect(collect($canvases)->pluck('key')->all())->toBe(['cover', 'invitation', 'event']);

        foreach ($canvases as $canvas) {
            expect($canvas['width'])->toBe(SceneComposer::WIDTH)
                ->and($canvas['height'])->toBe(SceneComposer::HEIGHT)
                // A canvas with nothing on it would render as a blank phone screen.
                ->and(count($canvas['layers']))->toBeGreaterThan(4);
        }
    }
});

it('ships every ornament a design draws with', function () {
    $missing = [];

    foreach (SiteTemplate::active()->get() as $design) {
        foreach ($design->canvases() as $canvas) {
            foreach ($canvas['layers'] as $layer) {
                if (filled($layer['src'] ?? null) && ! file_exists(public_path($layer['src']))) {
                    $missing[$layer['src']] = true;
                }
            }
        }
    }

    expect(array_keys($missing))->toBe([]);
});

/**
 * Colours and faces are role tokens, never baked values. That is what lets a couple
 * recolour a card by changing ten CSS variables instead of recomposing 1,800 layers,
 * and a design that baked a colour in would silently ignore their palette.
 */
it('leaves every palette colour and type face as a role the renderer resolves', function () {
    $baked = [];

    foreach (SiteTemplate::active()->get() as $design) {
        foreach ($design->canvases() as $canvas) {
            foreach ($canvas['layers'] as $layer) {
                foreach (['color', 'color2', 'fill', 'fill2'] as $field) {
                    $value = $layer[$field] ?? null;

                    // A handful of colours belong to the object being drawn rather
                    // than to the palette: black shades and vignettes, the white of
                    // polaroid paper, the black of film stock, the red of a REC dot.
                    // Those stay literal on purpose; anything else is a bug.
                    $artwork = ['#000000', '#ffffff', '#fdfcf8', '#0a0908', '#0c0b0a', '#e9e4d8', '#e5484d'];

                    if ($value !== null && ! Palettes::isRole($value) && ! in_array($value, $artwork, true)) {
                        $baked[$design->slug.'.'.$field.'='.$value] = true;
                    }
                }

                if (($layer['type'] ?? '') === 'text') {
                    expect($layer['font'])->toStartWith('role:');
                }
            }
        }
    }

    expect(array_keys($baked))->toBe([]);
});

/**
 * The cards are Malay by decision (.ai/rules/lang.md). The designs came from an
 * English catalogue, so a new composition is one copy-paste away from printing
 * "THE WEDDING OF" on a Malay invitation.
 */
it('keeps every word printed on the artwork in Malay', function () {
    $english = [];

    foreach (SiteTemplate::active()->get() as $design) {
        foreach ($design->canvases() as $canvas) {
            foreach ($canvas['layers'] as $layer) {
                if (($layer['type'] ?? '') !== 'text') {
                    continue;
                }

                $text = preg_replace('/\{\{[a-z0-9_]+\}\}/', '', $layer['text']);

                if (preg_match('/\b(the|wedding|of|and|invited|your|with|love|our|are|time|venue|place|when|where|address|say|issue|vol|together|families|presence)\b/i', $text)) {
                    $english[trim($text)] = true;
                }
            }
        }
    }

    expect(array_keys($english))->toBe([]);
});

it('asks only for photo slots the artwork actually has a frame for', function () {
    foreach (SiteTemplate::active()->get() as $design) {
        expect($design->photoSlots())->each->toBeIn(Catalog::photoSlotKeys());
    }
});

it('hands the card the design palette until the couple changes a colour', function () {
    $site = WeddingSite::factory()->published()->create(['template' => 'emerald-estate']);
    $design = SiteTemplate::where('slug', 'emerald-estate')->sole();

    $props = cardProps($this->get(cardUrlFor($site)));

    expect($props['vars']['--c-acc'])->toBe($design->palette()['acc']);

    $site->update(['palette' => ['acc' => '#123456']]);

    $props = cardProps($this->get(cardUrlFor($site)));

    expect($props['vars']['--c-acc'])->toBe('#123456')
        // Only what they changed; the rest of the design is untouched.
        ->and($props['vars']['--c-bg'])->toBe($design->palette()['bg']);
});

it('keeps each type face in its own role once it has been through the database', function () {
    $design = SiteTemplate::where('slug', 'royal-songket-gold')->sole();

    // MySQL normalises JSON object keys into alphabetical order, which is how the
    // set comes back in production; SQLite hands back what was written. Reading it
    // positionally would print the names in the small-caps face.
    $design->fonts = [
        'd' => 'Cormorant Garamond',
        'n' => 'Montserrat',
        'r' => 'Cormorant Garamond',
        's' => 'Great Vibes',
    ];

    expect($design->fonts())->toBe([
        'd' => 'Cormorant Garamond',
        's' => 'Great Vibes',
        'r' => 'Cormorant Garamond',
        'n' => 'Montserrat',
    ]);
});

it('falls back to the design face when a stored font is one we no longer ship', function () {
    $site = WeddingSite::factory()->published()->create(['template' => 'white-luxury']);
    $design = SiteTemplate::where('slug', 'white-luxury')->sole();

    $site->update(['fonts' => ['d' => 'Comic Sans MS']]);

    expect(cardProps($this->get(cardUrlFor($site)))['fonts']['d'])->toBe($design->fonts()['d']);
});

it('keeps the couple content out of the design and the design out of the content', function () {
    $site = WeddingSite::factory()->published()->create([
        'template' => 'royal-songket-gold',
        'groom_name' => 'Muhammad Hakim Ismail',
        'groom_short' => 'Hakim',
        'bride_name' => 'Nur Aina Zulkifli',
        'bride_short' => null,
        'venue_name' => 'Dewan Seri Melati',
    ]);

    $props = cardProps($this->get(cardUrlFor($site)));

    // The artwork carries tokens; the browser resolves them from `content`, which
    // is what makes the editor redraw the card as a couple types.
    expect($props['content']['groom_short'])->toBe('Hakim')
        // No short name was given for the bride, so her first name stands in.
        ->and($props['content']['bride_short'])->toBe('Nur')
        ->and($props['content']['venue'])->toBe('Dewan Seri Melati');

    $names = collect($props['canvases'])
        ->flatMap(fn (array $canvas): array => $canvas['layers'])
        ->where('type', 'text')
        ->pluck('text');

    expect($names->filter(fn (string $text): bool => str_contains($text, '{{')))->not->toBeEmpty()
        ->and($names->filter(fn (string $text): bool => str_contains($text, 'Hakim')))->toBeEmpty();
});

it('shows the sections in the order the couple arranged them', function () {
    $site = WeddingSite::factory()->published()->create([
        'venue_name' => 'Dewan Seri Melati',
        'closing_note' => 'Terima kasih.',
        'widgets' => ['closing', 'location', 'rsvp'],
    ]);

    expect(cardWidgets($this->get(cardUrlFor($site))))->toBe(['closing', 'location', 'rsvp']);
});

it('drops a section key that is not a section', function () {
    $site = WeddingSite::factory()->published()->create([
        'venue_name' => 'Dewan Seri Melati',
        'widgets' => ['location', 'drop-tables'],
    ]);

    expect(cardWidgets($this->get(cardUrlFor($site))))->toBe(['location']);
});

/*
|--------------------------------------------------------------------------
| Cards that existed before the layered designs
|--------------------------------------------------------------------------
|
| A published card is on people's phones already. The switch has to leave it
| recognisable: the nearest design rather than one flagship for everybody, and
| the photo they uploaded still on the card.
|
*/

it('replaces every retired design with one that still exists', function () {
    $live = SiteTemplate::active()->pluck('slug');

    expect(Catalog::REPLACES)->toHaveCount(24)
        ->and(collect(Catalog::REPLACES)->values()->unique()->every(fn (string $slug): bool => $live->contains($slug)))->toBeTrue();
});

it('keeps a card whose design was retired on a design of the same colour family', function () {
    // "Malam Emas" was navy and gold; Midnight Luxury is the same card at night.
    $site = WeddingSite::factory()->published()->create(['template' => Catalog::REPLACES['malam-emas']]);

    $props = cardProps($this->get(cardUrlFor($site)));

    expect($props['design']['slug'])->toBe('midnight-luxury')
        ->and($props['design']['dark'])->toBeTrue();
});

it('still renders a card whose design has been withdrawn altogether', function () {
    $site = WeddingSite::factory()->published()->create();
    // A row can outlive its design: the seeder deactivates a withdrawn one.
    $site->forceFill(['template' => 'reka-bentuk-yang-sudah-tiada'])->save();

    $this->get(cardUrlFor($site))->assertOk();

    expect(cardProps($this->get(cardUrlFor($site)))['canvases'])->toHaveCount(3);
});

it('shows the photo an older card uploaded as its cover wherever the design asks for the couple', function () {
    $site = WeddingSite::factory()->published()->create([
        'template' => 'neekah-signature',
        'cover_image' => 'sites/1/gambar.webp',
        'slot_images' => null,
    ]);

    // Most designs ask for couple_image; the photo was stored as the cover long
    // before slots existed, and it must not vanish from their card.
    expect($site->slotImage('couple_image'))->toEndWith('sites/1/gambar.webp')
        ->and($site->slotImage('cover_image'))->toEndWith('sites/1/gambar.webp')
        ->and($site->slotImage('groom_image'))->toBeNull();
});
