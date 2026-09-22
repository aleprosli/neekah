<?php

use App\Models\Category;
use App\Models\Package;
use App\Models\Review;
use App\Models\Setting;
use App\Models\SiteTemplate;
use App\Models\Vendor;
use App\Support\ContentVersion;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SiteTemplateSeeder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * What a public page costs the server. These are the two regressions that
 * actually hurt in production, where every query is a round trip.
 */
it('reads the settings store once however many callers ask for the values', function () {
    // The database store on purpose: it is what production runs, and it is the
    // one where every repeated read is a round trip. Under the array store the
    // repetition is free and this regression is invisible.
    config(['cache.default' => 'database']);

    Setting::put(['contact.email' => 'hai@neekah.my']);
    Cache::store('database')->flush();

    // Warm it, so what follows is repetition and nothing else.
    Setting::values();

    $reads = 0;
    DB::listen(function ($query) use (&$reads) {
        if (str_contains($query->sql, '`cache`') || str_contains($query->sql, '"cache"')) {
            $reads++;
        }
    });

    // A page render asks roughly this many times. Without memo() each one was
    // its own round trip to MySQL.
    for ($i = 0; $i < 20; $i++) {
        Setting::values();
    }

    // The point is that the cost is constant, not proportional: without memo()
    // this was twenty round trips to MySQL.
    expect($reads)->toBeLessThanOrEqual(1);
});

it('caches the gallery artwork but never the CSRF token of whoever asked first', function () {
    $this->seed(SiteTemplateSeeder::class);
    Cache::flush();

    $first = $this->get(route('sites.templates'))->assertOk()->getContent();

    // A second visitor, with a session and therefore a token of their own.
    $this->flushSession();
    $second = $this->get(route('sites.templates'))->assertOk()->getContent();

    $tokenOf = function (string $html): string {
        preg_match('/&quot;csrf&quot;:&quot;([^&]+)&quot;/', $html, $m);

        return $m[1] ?? '';
    };

    expect($tokenOf($first))->not->toBe('')
        ->and($tokenOf($second))->not->toBe('')
        ->and($tokenOf($second))->not->toBe($tokenOf($first));
});

it('rebuilds the gallery artwork when a design changes, with nothing to clear by hand', function () {
    $this->seed(SiteTemplateSeeder::class);
    Cache::flush();

    $this->get(route('sites.templates'))->assertOk()->assertSee('Royal Songket Gold');

    $template = SiteTemplate::where('slug', 'royal-songket-gold')->firstOrFail();
    $template->forceFill(['is_active' => false])->save();

    $this->get(route('sites.templates'))->assertOk()->assertDontSee('Royal Songket Gold');
});

it('points the media disk at the R2 bucket when the environment says so', function () {
    // config/filesystems.php is read once at boot, so the file itself is what
    // this reads back. The disk keeps the name "public" on purpose: every
    // caller and every Storage::fake('public') in the suite stays untouched.
    $with = function (array $env): array {
        foreach ($env as $key => $value) {
            $_SERVER[$key] = $value;
        }

        $disks = (require base_path('config/filesystems.php'))['disks'];

        foreach ($env as $key => $value) {
            unset($_SERVER[$key]);
        }

        return $disks['public'];
    };

    expect($with(['MEDIA_DISK' => 'local'])['driver'])->toBe('local');

    expect($with([
        'MEDIA_DISK' => 'r2',
        'R2_BUCKET' => 'neekah',
        'R2_ENDPOINT' => 'https://acc.r2.cloudflarestorage.com',
        'R2_URL' => 'https://cdn.neekah.my',
    ]))->toMatchArray([
        'driver' => 's3',
        'region' => 'auto',
        'bucket' => 'neekah',
        'url' => 'https://cdn.neekah.my',
        'use_path_style_endpoint' => true,
        'options' => ['CacheControl' => 'public, max-age=31536000, immutable'],
    ]);
});

/**
 * The public pages are cached against App\Support\ContentVersion. What these
 * guard is the pair of promises that makes that safe: the pages go on telling
 * the truth when something changes, and nothing an attacker could use comes
 * back out of the cache.
 */
describe('cached public pages', function () {
    beforeEach(function () {
        // The database store on purpose, as above: it is what production runs,
        // and config/cache.php forbids unserialising objects from it. Under the
        // array store a cached model round-trips happily and the bug this
        // caught would be invisible.
        config(['cache.default' => 'database']);
        Cache::store('database')->flush();

        $this->seed(CategorySeeder::class);
        $this->category = Category::where('slug', 'photography')->first();
    });

    it('stores no PHP objects, because a leaked APP_KEY must not become a gadget chain', function () {
        $vendor = Vendor::factory()->for($this->category)->create();
        Package::factory()->for($vendor)->create();

        $this->get('/')->assertOk();
        $this->get(route('vendors.show', $vendor))->assertOk();

        $rows = DB::table('cache')->pluck('value', 'key');

        expect($rows)->not->toBeEmpty();

        foreach ($rows as $key => $value) {
            // "O:" is a serialised object. Anything cached here is arrays and
            // scalars, hydrated back into models after it is read.
            expect($value)->not->toContain('O:', "cache entry [{$key}] holds an object");
        }
    });

    it('renders the same page from the cache as it did from the database', function () {
        $vendor = Vendor::factory()->for($this->category)->create();
        Package::factory()->for($vendor)->create(['name' => 'Pakej Penuh Hari']);

        // The <body>, because the layout emits its font preloads and @font-face
        // block @once per process: they are in the head of the first render of
        // a test run and not the second. That is the layout's business, not the
        // cache's, and over HTTP - where each request is its own process - the
        // two responses are identical byte for byte.
        $body = fn (string $html): string => mb_substr($html, mb_strpos($html, '<body') ?: 0);

        $cold = $body($this->get(route('vendors.show', $vendor))->assertOk()->getContent());
        $warm = $body($this->get(route('vendors.show', $vendor))->assertOk()->getContent());

        expect($warm)->toBe($cold)->toContain('Pakej Penuh Hari');
    });

    it('asks the database for less the second time a profile is read', function () {
        $vendor = Vendor::factory()->for($this->category)->create();
        Package::factory()->count(3)->for($vendor)->create();

        $count = function () use ($vendor): int {
            $queries = 0;
            DB::listen(function () use (&$queries): void {
                $queries++;
            });

            $this->get(route('vendors.show', $vendor))->assertOk();

            return $queries;
        };

        $cold = $count();

        expect($count())->toBeLessThan($cold);
    });

    it('shows a new package as soon as it is saved, with nothing to clear', function () {
        $vendor = Vendor::factory()->for($this->category)->create();

        $this->get(route('vendors.show', $vendor))->assertOk()->assertDontSee('Pakej Petang');

        Package::factory()->for($vendor)->create(['name' => 'Pakej Petang']);

        $this->get(route('vendors.show', $vendor))->assertOk()->assertSee('Pakej Petang');
    });

    it('shows a new review as soon as it is posted', function () {
        $vendor = Vendor::factory()->for($this->category)->create();

        $this->get(route('vendors.show', $vendor))->assertOk()->assertDontSee('Sangat berbaloi');

        Review::factory()->for($vendor)->create(['comment' => 'Sangat berbaloi']);

        $this->get(route('vendors.show', $vendor))->assertOk()->assertSee('Sangat berbaloi');
    });

    it('puts a newly approved vendor on the listing without waiting for the cache to expire', function () {
        $this->get('/')->assertOk()->assertDontSee('Studio Bunga Raya');

        Vendor::factory()->for($this->category)->create(['name' => 'Studio Bunga Raya']);

        $this->get('/')->assertOk()->assertSee('Studio Bunga Raya');
    });

    it('drops one vendor\'s cached page without dropping another\'s', function () {
        $edited = Vendor::factory()->for($this->category)->create();
        $untouched = Vendor::factory()->for($this->category)->create();

        $this->get(route('vendors.show', $edited))->assertOk();
        $this->get(route('vendors.show', $untouched))->assertOk();

        $editedBefore = ContentVersion::forVendor($edited->getKey());
        $untouchedBefore = ContentVersion::forVendor($untouched->getKey());

        Package::factory()->for($edited)->create(['name' => 'Pakej Baharu']);

        // The point of the per-vendor scope: one vendor adding a package must
        // not throw away the other three hundred profiles.
        expect(ContentVersion::forVendor($edited->getKey()))->not->toBe($editedBefore)
            ->and(ContentVersion::forVendor($untouched->getKey()))->toBe($untouchedBefore);

        $this->get(route('vendors.show', $edited))->assertOk()->assertSee('Pakej Baharu');
    });
});
