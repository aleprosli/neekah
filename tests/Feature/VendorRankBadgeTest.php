<?php

use App\Enums\VendorTier;
use Illuminate\Support\Facades\Blade;

it('gives every badge on a page its own gradient ids, the same ones on every render', function () {
    $render = fn (): string => Blade::render('<x-vendor-rank-badge :tier="$tier" /><x-vendor-rank-badge :tier="$tier" />', ['tier' => VendorTier::Top]);

    $page = $render();
    preg_match_all('/<linearGradient id="(rb\d+)-face"/', $page, $ids);

    expect($ids[1])->toHaveCount(2)
        ->and(array_unique($ids[1]))->toHaveCount(2);

    // A fresh request renders the same page with the same ids.
    request()->attributes->remove('rank-badges');
    expect($render())->toBe($page);
});
