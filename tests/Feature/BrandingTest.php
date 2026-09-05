<?php

use App\Models\WeddingSite;
use Database\Seeders\SiteTemplateSeeder;

it('draws the header logo, the tab icon and the preloader from one config block', function () {
    config()->set('neekah.brand', [
        'lockup' => 'img/logo/custom-lockup.png',
        'mark' => 'img/logo/custom-mark.png',
        'icon' => 'img/logo/custom-icon.png',
        'apple_icon' => 'img/logo/custom-apple.png',
    ]);

    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee(asset('img/logo/custom-lockup.png'))
        ->assertSee(asset('img/logo/custom-icon.png'))
        ->assertSee(asset('img/logo/custom-apple.png'));
});

it('ships the artwork the config points at by default', function () {
    foreach (config('neekah.brand') as $path) {
        expect(public_path($path))->toBeFile();
    }
});

it('covers the page with a preloader that javascript takes down', function () {
    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('id="nk-preloader"', false)
        ->assertSee('Memuatkan Neekah');
});

it('opens an invitation in the couple own colours, never the neekah brand', function () {
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create([
        'template' => 'seri-gangsa',
        'bride_name' => 'Aina',
        'groom_name' => 'Hakim',
    ]);

    $response = $this->get('http://'.$site->subdomain.'.'.config('neekah.site_domain').'/')->assertOk();

    $response->assertSee('class="nk-preloader nk-card"', false)
        ->assertSee('--nk-page:', false)
        ->assertSee('Memuatkan Aina &amp; Hakim', false);

    // Guests are the couple's, not ours. Our logo has no place in front of
    // the card they were invited to.
    expect($response->getContent())->not->toContain(asset(config('neekah.brand.lockup')));
});

it('reads the preloader hold from config and tells the browser about it', function () {
    config()->set('neekah.preloader.seconds', 3.5);

    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('data-min-seconds="3.5"', false)
        // The failsafe has to outlast the hold, or it would uncover the page early.
        ->assertSee('--nk-preloader-failsafe: 8.5s', false);
});

it('sends whatever hold this deployment configured through to the browser', function () {
    // Deliberately not pinned to a number. The shipped default lives in
    // config/neekah.php and each deployment is free to tune it in .env.
    $seconds = config('neekah.preloader.seconds');

    $this->get(route('vendors.index'))->assertOk()->assertSee('data-min-seconds="'.$seconds.'"', false);
});

it('accepts a zero hold, so the page shows as soon as it is ready', function () {
    config()->set('neekah.preloader.seconds', 0);

    $this->get(route('vendors.index'))->assertOk()->assertSee('data-min-seconds="0"', false);
});

it('keeps the white strokes in the lockup solid, not see through', function () {
    // Keying the cream board out by colour alone turned the white script into a
    // half transparent ghost, because white is only 26 apart from the cream.
    $image = imagecreatefrompng(public_path(config('neekah.brand.lockup')));
    $solid = 0;
    $white = 0;

    for ($y = 0; $y < imagesy($image); $y++) {
        for ($x = 0; $x < imagesx($image); $x++) {
            $colour = imagecolorat($image, $x, $y);
            $alpha = ($colour >> 24) & 0x7F;

            if ($alpha < 10 && (($colour >> 16) & 0xFF) > 235 && (($colour >> 8) & 0xFF) > 235 && ($colour & 0xFF) > 235) {
                $white++;
                $solid += $alpha === 0 ? 1 : 0;
            }
        }
    }

    expect($white)->toBeGreaterThan(1000)
        ->and($solid / $white)->toBeGreaterThan(0.95);
});
