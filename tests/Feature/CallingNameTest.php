<?php

use App\Models\WeddingSite;
use App\Support\CallingName;
use Database\Seeders\SiteTemplateSeeder;

/**
 * Taking the first word names the wrong person for most Malaysian names, and the
 * name on a wedding card is the one thing that has to be right.
 */
it('skips the honorific a Malaysian name opens with', function (string $full, string $expected) {
    expect(CallingName::from($full))->toBe($expected);
})->with([
    ['Muhammad Faez Hakimi Bin Mohd Samsol Baha', 'Faez'],
    ['Nur Aina Adriana Binti Abdullah', 'Aina'],
    ['Mohd. Firdaus bin Rahman', 'Firdaus'],
    ['Siti Nurhaliza Tarudin', 'Nurhaliza'],
    ['Nurul Izzah Anwar', 'Izzah'],
    // Two honorifics in a row: skipping one would leave the other on the cover.
    ['Muhammad Nur Haikal Firdaus Bin Roslan', 'Haikal'],
    ['Nur Fa\'izah Binti Muhamad Tamizi', 'Fa\'izah'],
    // Left alone: these are the names people are actually called by.
    ['Ahmad Zulkifli', 'Ahmad'],
    ['Wan Aisyah Wan Ismail', 'Wan'],
    ['Hakim Ismail', 'Hakim'],
    // Nothing better to fall back to.
    ['Nur', 'Nur'],
    ['', ''],
]);

it('prints the name a couple typed over the one we would have guessed', function () {
    $this->seed(SiteTemplateSeeder::class);

    $site = WeddingSite::factory()->create([
        'groom_name' => 'Muhammad Faez Hakimi',
        'groom_short' => 'Hakimi',
        'bride_name' => 'Nur Aina Adriana',
        'bride_short' => null,
    ]);

    expect($site->shortName('groom'))->toBe('Hakimi')
        ->and($site->shortName('bride'))->toBe('Aina');
});

it('tells a derived short name apart from one that was typed', function () {
    expect(CallingName::looksDerived('Muhammad', 'Muhammad Faez Hakimi'))->toBeTrue()
        ->and(CallingName::looksDerived('Faez', 'Muhammad Faez Hakimi'))->toBeFalse()
        ->and(CallingName::looksDerived(null, 'Muhammad Faez'))->toBeFalse();
});
