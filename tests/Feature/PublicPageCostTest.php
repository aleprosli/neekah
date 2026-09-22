<?php

use App\Models\Setting;
use App\Models\SiteTemplate;
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
