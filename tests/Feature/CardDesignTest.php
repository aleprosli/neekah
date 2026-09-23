<?php

use App\Models\CardMusicTrack;
use App\Models\SiteTemplate;
use App\Models\WeddingSite;
use App\Support\Card\CardProps;
use App\Support\Card\Catalog;
use App\Support\Card\Palettes;
use App\Support\Card\SampleCard;
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

it('offers ten illustrated cards from four shared lightweight assets', function () {
    $designs = SiteTemplate::active()->where('slug', 'like', 'ekad-%')->get();
    $backgrounds = [];
    $animatedFrames = 0;

    expect($designs)->toHaveCount(10);

    foreach ($designs as $design) {
        $canvases = $design->canvases();
        $cover = $canvases[0]['layers'];

        expect($cover[0]['src'])->toStartWith('/img/layers/ekad-')
            ->and($cover[0]['type'])->toBe('image')
            ->and(end($cover)['src'])->toBe('/img/layers/ekad-floral-frame.webp')
            ->and(end($cover)['motion'])->toBeTrue();

        $backgrounds[$cover[0]['src']] = true;
        $animatedFrames++;

        foreach ($canvases as $canvas) {
            expect($canvas['layers'][0]['src'])->toBe($cover[0]['src']);
            expect($canvas['layers'][0]['widgetBackground'])->toBeTrue();
        }
    }

    expect(array_keys($backgrounds))->toHaveCount(3)
        ->and($animatedFrames)->toBe(10);

    foreach ([...array_keys($backgrounds), '/img/layers/ekad-floral-frame.webp'] as $asset) {
        expect(filesize(public_path($asset)))->toBeLessThan(300_000)
            ->and(getimagesize(public_path($asset))['mime'])->toBe('image/webp');
    }
});

it('offers the lightweight mihrab artwork throughout the invitation', function () {
    $design = SiteTemplate::where('slug', 'mihrab-mawar')->sole();
    $background = '/img/layers/ekad-mihrab-bloom.webp';

    foreach ($design->canvases() as $canvas) {
        expect($canvas['layers'][0]['src'])->toBe($background)
            ->and($canvas['layers'][0]['widgetBackground'])->toBeTrue()
            ->and($canvas['layers'][0]['motion'])->toBeTrue();
    }

    expect(filesize(public_path($background)))->toBeLessThan(150_000)
        ->and(getimagesize(public_path($background))['mime'])->toBe('image/webp');
});

it('composes the olive and burgundy motion card from separate lightweight artwork', function () {
    $design = SiteTemplate::where('slug', 'sutera-zaitun')->sole();
    $assets = [];

    expect($design->experience)->toBe('motion');

    foreach ($design->canvases() as $canvas) {
        $images = collect($canvas['layers'])->where('type', 'image');

        expect($images->count())->toBeGreaterThanOrEqual(3)
            ->and($images->first()['src'])->toBe('/img/layers/sutera-satin.webp')
            ->and($images->first()['widgetBackground'])->toBeTrue()
            ->and($images->where('motion', true)->count())->toBeGreaterThanOrEqual(2);

        foreach ($images as $image) {
            $assets[$image['src']] = true;
        }
    }

    expect(array_keys($assets))->toHaveCount(4);

    foreach (array_keys($assets) as $asset) {
        expect(file_exists(public_path($asset)))->toBeTrue()
            ->and(filesize(public_path($asset)))->toBeLessThan(160_000)
            ->and(getimagesize(public_path($asset))['mime'])->toBe('image/webp');
    }
});

it('composes Lili Kasih as a layered motion card with lightweight original artwork', function () {
    $design = SiteTemplate::where('slug', 'lili-kasih')->sole();
    $assets = [];

    expect($design->experience)->toBe('motion');

    foreach ($design->canvases() as $canvas) {
        $images = collect($canvas['layers'])->where('type', 'image');

        expect($images->count())->toBeGreaterThanOrEqual(3)
            ->and($images->first()['src'])->toBe('/img/layers/lili-paper.webp')
            ->and($images->first()['widgetBackground'])->toBeTrue()
            ->and($images->last()['src'])->toBe('/img/layers/lili-flying-butterflies.gif')
            ->and($images->last()['widgetAnimation'])->toBeTrue()
            ->and($images->slice(-2, 1)->first()['src'])->toBe('/img/layers/lili-arch.webp')
            ->and($images->slice(-2, 1)->first()['widgetForeground'])->toBeTrue()
            ->and($images->where('motion', true)->count())->toBeGreaterThanOrEqual(2);

        foreach ($images as $image) {
            $assets[$image['src']] = true;
        }
    }

    expect(array_keys($assets))->toHaveCount(6);

    foreach (array_keys($assets) as $asset) {
        expect(file_exists(public_path($asset)))->toBeTrue();

        if (str_ends_with($asset, '.gif')) {
            expect(filesize(public_path($asset)))->toBeLessThan(50_000)
                ->and(getimagesize(public_path($asset))['mime'])->toBe('image/gif')
                ->and(substr_count(file_get_contents(public_path($asset)), "\x21\xF9\x04"))->toBeGreaterThan(1);
        } else {
            expect(filesize(public_path($asset)))->toBeLessThan(100_000)
                ->and(getimagesize(public_path($asset))['mime'])->toBe('image/webp');
        }
    }
});

it('composes Taman Bulan with a consistent night scene and butterflies ahead of its gateway', function () {
    $design = SiteTemplate::where('slug', 'taman-bulan')->sole();
    $assets = [];

    expect($design->experience)->toBe('motion');

    foreach ($design->canvases() as $canvas) {
        $images = collect($canvas['layers'])->where('type', 'image')->values();

        expect($images->first()['src'])->toBe('/img/layers/taman-bulan-night.webp')
            ->and($images->first()['widgetBackground'])->toBeTrue()
            ->and($images->get($images->count() - 2)['src'])->toBe('/img/layers/taman-bulan-arch.webp')
            ->and($images->get($images->count() - 2)['widgetForeground'])->toBeTrue()
            ->and($images->last()['src'])->toBe('/img/layers/lili-flying-butterflies.gif')
            ->and($images->last()['widgetAnimation'])->toBeTrue();

        foreach ($images as $image) {
            $assets[$image['src']] = true;
        }
    }

    expect(array_keys($assets))->toHaveCount(3);

    foreach (['taman-bulan-night.webp', 'taman-bulan-arch.webp'] as $asset) {
        $path = public_path('img/layers/'.$asset);

        expect(file_exists($path))->toBeTrue()
            ->and(filesize($path))->toBeLessThan(300_000)
            ->and(getimagesize($path)['mime'])->toBe('image/webp');
    }
});

it('starts the Taman Bulan sample with the bundled instrumental and allows music to be disabled', function () {
    $design = SiteTemplate::where('slug', 'taman-bulan')->sole();
    $sample = SampleCard::site($design);

    expect(CardProps::forSample($design, $sample)['music'])->toBe([
        'url' => asset('sutera-zaitun-music.mp3'),
        'title' => 'Wedding Harp — Francisco Alvear',
    ]);

    $sample->music_enabled = false;

    expect(CardProps::forSample($design, $sample)['music'])->toBeNull();
});

it('uses distinct lightweight wax seals for Taman Bulan and Lili Kasih', function () {
    $seals = [
        'taman-bulan' => '/img/layers/taman-bulan-wax-seal.webp',
        'lili-kasih' => '/img/layers/lili-wax-seal.webp',
    ];

    foreach ($seals as $slug => $seal) {
        $design = SiteTemplate::where('slug', $slug)->sole();

        expect(CardProps::forSample($design, SampleCard::site($design))['gate']['seal'])->toBe($seal)
            ->and(file_exists(public_path($seal)))->toBeTrue()
            ->and(filesize(public_path($seal)))->toBeLessThan(50_000)
            ->and(getimagesize(public_path($seal))['mime'])->toBe('image/webp');
    }

    $otherDesign = SiteTemplate::where('slug', 'sutera-zaitun')->sole();

    expect(CardProps::forSample($otherDesign, SampleCard::site($otherDesign))['gate']['seal'])->toBeNull();
});

it('adds a lightweight textured opening only to Lili Kasih', function () {
    $design = SiteTemplate::where('slug', 'lili-kasih')->sole();
    $otherDesign = SiteTemplate::where('slug', 'taman-bulan')->sole();
    $texture = '/img/layers/lili-gate-paper.webp';

    expect(CardProps::forSample($design, SampleCard::site($design))['gate']['texture'])->toBe($texture)
        ->and(CardProps::forSample($otherDesign, SampleCard::site($otherDesign))['gate']['texture'])->toBeNull()
        ->and(file_exists(public_path($texture)))->toBeTrue()
        ->and(filesize(public_path($texture)))->toBeLessThan(80_000)
        ->and(getimagesize(public_path($texture))['mime'])->toBe('image/webp');
});

it('keeps Sutera Zaitun text legible on its darker silk folds', function () {
    $palette = SiteTemplate::where('slug', 'sutera-zaitun')->sole()->palette();
    $luminance = static function (string $hex): float {
        $channels = array_map(static fn (int $offset): float => hexdec(substr($hex, $offset, 2)) / 255, [1, 3, 5]);
        $linear = array_map(static fn (float $channel): float => $channel <= 0.04045 ? $channel / 12.92 : (($channel + 0.055) / 1.055) ** 2.4, $channels);

        return $linear[0] * 0.2126 + $linear[1] * 0.7152 + $linear[2] * 0.0722;
    };
    $foldLuminance = $luminance('#CBBDAA');

    foreach (['onbg', 'ink', 'pri', 'acc', 'mut'] as $role) {
        $textLuminance = $luminance($palette[$role]);
        $contrast = ($foldLuminance + 0.05) / ($textLuminance + 0.05);

        expect($contrast)->toBeGreaterThanOrEqual(4.5);
    }
});

it('plays the bundled instrumental only when music is enabled on Sutera Zaitun', function () {
    $design = SiteTemplate::where('slug', 'sutera-zaitun')->sole();
    $sample = SampleCard::site($design);

    expect(CardProps::forSample($design, $sample)['music'])->toBe([
        'url' => asset('sutera-zaitun-music.mp3'),
        'title' => 'Wedding Harp — Francisco Alvear',
    ])->and(file_exists(public_path('sutera-zaitun-music.mp3')))->toBeTrue();

    $sample->music_enabled = false;

    expect(CardProps::forSample($design, $sample)['music'])->toBeNull();

    $sample->music_enabled = true;
    $track = CardMusicTrack::factory()->create();
    $sample->setRelation('musicTrack', $track);

    expect(CardProps::forSample($design, $sample)['music'])->toBe([
        'url' => $track->url(),
        'title' => $track->label(),
    ]);

    $otherDesign = SiteTemplate::where('slug', 'mihrab-mawar')->sole();

    expect(CardProps::forSample($otherDesign, SampleCard::site($otherDesign))['music'])->toBeNull();
});

it('plays Satu Shaf for Lili Kasih unless music is disabled or replaced', function () {
    $design = SiteTemplate::where('slug', 'lili-kasih')->sole();
    $sample = SampleCard::site($design);

    expect(CardProps::forSample($design, $sample)['music'])->toBe([
        'url' => asset('lili-kasih-music.mp3'),
        'title' => 'Satu Shaf',
    ])->and(file_exists(public_path('lili-kasih-music.mp3')))->toBeTrue()
        ->and(filesize(public_path('lili-kasih-music.mp3')))->toBeLessThan(500_000);

    $sample->music_enabled = false;

    expect(CardProps::forSample($design, $sample)['music'])->toBeNull();

    $sample->music_enabled = true;
    $track = CardMusicTrack::factory()->create();
    $sample->setRelation('musicTrack', $track);

    expect(CardProps::forSample($design, $sample)['music'])->toBe([
        'url' => $track->url(),
        'title' => $track->label(),
    ]);
});

it('plays motion cards as scenes while preserving scrolling for existing cards', function () {
    $motionDesign = SiteTemplate::where('slug', 'mihrab-mawar')->sole();
    $scrollDesign = SiteTemplate::where('slug', 'ekad-ivory-heirloom')->sole();

    expect($motionDesign->experience)->toBe('motion')
        ->and(cardProps($this->get(route('sites.templates.show', $motionDesign)))['experience'])->toBe('motion')
        ->and($scrollDesign->experience)->toBe('scroll')
        ->and(cardProps($this->get(route('sites.templates.show', $scrollDesign)))['experience'])->toBe('scroll');
});

it('builds five more motion cards from artwork the catalogue already ships', function () {
    $shipped = collect(SiteTemplate::whereIn('slug', ['mihrab-mawar', 'sutera-zaitun', 'lili-kasih', 'taman-bulan'])->get())
        ->merge(SiteTemplate::where('slug', 'like', 'ekad-%')->get())
        ->flatMap(fn (SiteTemplate $design) => collect($design->canvases())->flatMap(fn (array $canvas) => collect($canvas['layers'])->where('type', 'image')->pluck('src')))
        ->unique();
    $sounds = [
        'kasih-sutera' => 'lili-kasih-music.mp3',
        'melur-purnama' => 'sutera-zaitun-music.mp3',
        'mihrab-kasih' => 'lili-kasih-music.mp3',
        'taman-zaitun' => 'sutera-zaitun-music.mp3',
        'gerbang-wisteria' => 'sutera-zaitun-music.mp3',
    ];

    foreach ($sounds as $slug => $sound) {
        $design = SiteTemplate::where('slug', $slug)->sole();
        $props = CardProps::forSample($design, SampleCard::site($design));

        expect($design->experience)->toBe('motion')
            ->and($props['music']['url'])->toBe(asset($sound))
            ->and($props['gate']['seal'])->not->toBeNull();

        foreach ($design->canvases() as $canvas) {
            $images = collect($canvas['layers'])->where('type', 'image');

            expect($images->first()['widgetBackground'])->toBeTrue()
                ->and($images->where('motion', true))->not->toBeEmpty()
                ->and($images->pluck('src')->diff($shipped)->all())->toBe([]);
        }
    }
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
        // No short name was given for the bride, so the name she is called by
        // stands in — not "Nur", which opens half the names in the country.
        ->and($props['content']['bride_short'])->toBe('Aina')
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
