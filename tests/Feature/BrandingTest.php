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

it('keeps the neekah preloader off a couple invitation card', function () {
    $this->seed(SiteTemplateSeeder::class);
    $site = WeddingSite::factory()->published()->create();

    // The card opens with its own gate. Our brand must not sit in front of it.
    $this->get('http://'.$site->subdomain.'.'.config('neekah.site_domain').'/')
        ->assertOk()
        ->assertDontSee('id="nk-preloader"', false);
});
