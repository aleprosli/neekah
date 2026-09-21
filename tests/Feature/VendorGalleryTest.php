<?php

use Illuminate\Support\Str;

/**
 * The gallery is drawn by Vue, so its markup never reaches a server-rendered
 * response. These read the component itself, which is the only place the rule
 * can be stated.
 */
it('shows a portfolio photo whole rather than cropping half of it away', function () {
    $component = file_get_contents(resource_path('js/components/public/PortfolioGallery.vue'));

    // Vendors upload portrait photos straight off a phone. The cell is a
    // fixed-height landscape box, so object-cover hid about half of each one:
    // a 1353x1920 photo showed 52% of itself, the top and bottom simply gone.
    $grid = Str::between($component, '<div class="grid h-72', '</div>');

    expect($grid)
        ->toContain('object-contain')
        ->not->toContain('size-full object-cover transition');

    // Filled, not letterboxed onto a bare rectangle.
    expect($grid)->toContain('blur-xl');
});

it('lets a screen reader hear the photo once, not twice', function () {
    $component = file_get_contents(resource_path('js/components/public/PortfolioGallery.vue'));
    $grid = Str::between($component, '<div class="grid h-72', '</div>');

    // The backdrop is the same photo again, so it carries no alt text.
    expect($grid)->toContain('aria-hidden="true"');
});
