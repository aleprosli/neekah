<?php

use App\Models\User;
use App\Support\BoostSettings;
use App\Support\CameraSettings;
use App\Support\ProSettings;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

function putEveryFeatureOnSale(): void
{
    app(CameraSettings::class)->save(['enabled' => true]);
    app(ProSettings::class)->save(['enabled' => true]);
    app(BoostSettings::class)->save(['enabled' => true]);
}

it('lists in the About dropdown only the feature pages on sale', function () {
    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSee('data-popover', false)
        ->assertSee(route('features.marketplace'))
        ->assertDontSee(route('features.kenangan'))
        ->assertDontSee(route('features.pro'))
        ->assertDontSee(route('features.boost'));

    putEveryFeatureOnSale();

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeInOrder([
            __('pages.features.menu.couples'), __('pages.features.marketplace.name'), __('pages.features.kenangan.name'),
            __('pages.features.menu.vendors'), __('pages.features.pro.name'), __('pages.features.boost.name'),
        ]);
});

it('gives the phone the same menu from the bottom bar, since its header has none', function () {
    putEveryFeatureOnSale();

    $html = $this->get(route('features.marketplace'))->assertOk()->getContent();
    preg_match('/<nav data-nav-region class="fixed[^"]*"[^>]*>.*?<\/nav>/s', $html, $bottomBar);

    expect($bottomBar[0])
        ->toContain('data-popover')
        ->toContain(route('features.marketplace'))
        ->toContain(route('features.kenangan'))
        ->toContain(route('features.pro'))
        ->toContain(route('features.boost'))
        ->toContain(route('blog.index'));
});

it('ends each feature page with links to the others but not itself', function () {
    putEveryFeatureOnSale();

    $this->get(route('features.pro'))
        ->assertOk()
        ->assertSee('data-feature-card="marketplace"', false)
        ->assertSee('data-feature-card="kenangan"', false)
        ->assertSee('data-feature-card="boost"', false)
        ->assertDontSee('data-feature-card="pro"', false);
});

it('serves every feature page in English under /en', function (string $key) {
    putEveryFeatureOnSale();

    $this->get(url()->routeIn('en', 'features.'.$key))
        ->assertOk()
        ->assertSee(__('pages.features.menu.about', locale: 'en'))
        ->assertSee(__('pages.features.explore.more', locale: 'en'))
        ->assertDontSee(__('pages.features.explore.more', locale: 'ms'));
})->with(['marketplace', 'kenangan', 'pro', 'boost']);

it('sends a signed-in vendor to subscribe rather than to sign up', function () {
    putEveryFeatureOnSale();

    $this->get(route('features.pro'))->assertSee(route('vendor.register'));

    $this->actingAs(User::factory()->vendor()->create())
        ->get(route('features.pro'))
        ->assertOk()
        ->assertSee(route('vendor.pro.index'))
        ->assertSee(__('pages.features.pro.subscribe'));
});

it('puts only the feature pages on sale in the sitemap', function () {
    $xml = $this->get(route('sitemap.pages'))->assertOk()->getContent();

    expect($xml)
        ->toContain('<loc>'.route('features.marketplace').'</loc>')
        ->not->toContain('<loc>'.route('features.kenangan').'</loc>');

    putEveryFeatureOnSale();

    expect($this->get(route('sitemap.pages'))->getContent())
        ->toContain('<loc>'.route('features.kenangan').'</loc>')
        ->toContain('<loc>'.url()->routeIn('en', 'features.boost').'</loc>');
});
