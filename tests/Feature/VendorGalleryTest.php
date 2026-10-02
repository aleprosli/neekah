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
    // The first tile only; the lightbox further down still shows a photo whole.
    $tile = Str::before(Str::after($component, '<div class="grid h-[26rem]'), '</button>');

    expect($tile)
        ->toContain('object-cover')
        ->not->toContain('object-contain')
        ->not->toContain('blur-xl');

    // The server-side grid Google reads is the same height, so nothing jumps
    // when the island mounts over it.
    expect(file_get_contents(resource_path('views/vendors/show.blade.php')))->toContain('grid h-[26rem] grid-cols-4');
});

it('lets a screen reader hear the photo once, not twice', function () {
    $component = file_get_contents(resource_path('js/components/public/PortfolioGallery.vue'));
    // The first tile only: Str::between would run to the lightbox's last button.
    $tile = Str::before(Str::after($component, '<div class="grid h-[26rem]'), '</button>');

    // One image per tile, with the caption as its alt text: no backdrop copy
    // that would have to be hidden from a screen reader.
    expect(substr_count($tile, '<img'))->toBe(1)
        ->and($tile)->toContain(':alt="photo.caption || vendorName"');
});

it('centers the thumbnail list on the current lightbox image', function () {
    $component = file_get_contents(resource_path('js/components/public/PortfolioGallery.vue'));

    expect($component)
        ->toContain('ref="thumbnailTrack"')
        ->toContain('px-[calc(50%-1.75rem)]')
        ->toContain('snap-center')
        ->toContain('centerCurrentThumbnail')
        ->toContain('track.scrollTo({ left: centeredLeft, behavior })')
        ->toContain('watch(index, () => centerCurrentThumbnail())');
});
