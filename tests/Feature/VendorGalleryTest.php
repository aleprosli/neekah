<?php

use Illuminate\Support\Str;

/**
 * The gallery is drawn by Vue, so its markup never reaches a server-rendered
 * response. These read the component itself, which is the only place the rule
 * can be stated.
 */
it('fills each tile with the photo, on a tile tall enough for a portrait', function () {
    $component = file_get_contents(resource_path('js/components/public/PortfolioGallery.vue'));

    // Vendors upload portrait photos straight off a phone. The tile used to be a
    // landscape box, so the photo was letterboxed over a blurred copy of itself,
    // which the owner read as a broken image (Sep 2026). The tile is tall now
    // and the photo covers it; the lightbox is where a photo is seen whole.
    $grid = Str::between($component, '<div class="grid h-[26rem]', '</div>');

    expect($grid)
        ->toContain('object-cover')
        ->not->toContain('object-contain')
        ->not->toContain('blur-xl');

    // The server-side grid Google reads is the same height, so nothing jumps
    // when the island mounts over it.
    expect(file_get_contents(resource_path('views/vendors/show.blade.php')))->toContain('grid h-[26rem] grid-cols-4');
});

it('lets a screen reader hear the photo once, not twice', function () {
    $component = file_get_contents(resource_path('js/components/public/PortfolioGallery.vue'));
    $grid = Str::between($component, '<div class="grid h-[26rem]', '</button>');

    // One image per tile, with the caption as its alt text: no backdrop copy
    // that would have to be hidden from a screen reader.
    expect(substr_count($grid, '<img'))->toBe(1)
        ->and($grid)->toContain(':alt="photo.caption || vendorName"');
});
