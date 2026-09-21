<?php

use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('points at a status page hosted somewhere other than here', function () {
    // A status page served by the thing it reports on is no use when that
    // thing is the problem.
    $html = $this->get(route('landing'))->assertOk()->getContent();

    expect($html)
        ->toContain(config('neekah.status_url'))
        ->toContain(__('pages.footer.status_sistem'));

    expect(parse_url(config('neekah.status_url'), PHP_URL_HOST))
        ->not->toBe(parse_url(config('app.url'), PHP_URL_HOST));
});

it('drops the status link when no status page is configured', function () {
    config(['neekah.status_url' => '']);

    expect($this->get(route('landing'))->assertOk()->getContent())
        ->not->toContain(__('pages.footer.status_sistem'));
});

it('opens the status page in a tab of its own, without leaking the referrer chain', function () {
    $html = $this->get(route('landing'))->assertOk()->getContent();

    expect($html)->toMatch('/<a href="'.preg_quote(config('neekah.status_url'), '/').'"[^>]*rel="noopener"/');
});

it('shows only the language you would move to when the header is narrow', function () {
    $html = $this->get(route('landing'))->assertOk()->getContent();

    // Both halves are in the markup; the one you are already reading is the
    // one that stands down on a phone.
    expect($html)
        ->toContain('>MS</a>')
        ->toContain('>EN</a>')
        ->toContain('hidden bg-brand-600 text-white sm:block');

    // And the one you would move to says where it goes.
    expect($html)->toContain(__('nav.switch_to', ['language' => 'English']));
});

it('lets the logo give ground so the header cannot overflow a phone', function () {
    // The buttons beside it are wider in English than in Malay, so the header
    // must not depend on any of them being a particular width.
    $header = file_get_contents(resource_path('views/components/site/header.blade.php'));

    expect($header)
        ->toContain('min-w-0 shrink items-center')
        ->toContain('max-w-full')
        ->not->toContain('max-w-[42vw]');
});
