<?php

use App\Support\States;

it('ships a flag for every negeri in the config', function () {
    // A negeri with no artwork silently loses its flag everywhere, which reads
    // as a broken page rather than a missing file.
    $missing = array_values(array_filter(
        States::names(),
        fn (string $state): bool => ! file_exists(public_path('img/flag/'.States::slug($state).'.svg')),
    ));

    expect($missing)->toBe([]);
});

it('names a flag after the slug the config gives the negeri', function () {
    expect(States::flagUrl('Pulau Pinang'))->toEndWith('/img/flag/pulau-pinang.svg')
        ->and(States::flagUrl('Negeri Sembilan'))->toEndWith('/img/flag/negeri-sembilan.svg');
});

it('reads the list from config, so a negeri can be added without touching a model', function () {
    config()->set('states', [['name' => 'Wilayah Baharu', 'slug' => 'wilayah-baharu']]);

    expect(States::names())->toBe(['Wilayah Baharu'])
        ->and(States::has('Selangor'))->toBeFalse()
        ->and(States::flagUrl('Wilayah Baharu'))->toEndWith('/img/flag/wilayah-baharu.svg');
});

it('has no flag for something that is not a negeri', function () {
    expect(States::flagUrl('Singapura'))->toBeNull()
        ->and(States::flagUrl(null))->toBeNull();
});

it('offers every negeri with its flag as a dropdown option', function () {
    $options = States::options();

    expect($options)->toHaveCount(count(States::names()));
    expect($options[0])->toMatchArray(['value' => 'Kedah', 'label' => 'Kedah']);
    expect(collect($options)->pluck('flag')->filter())->toHaveCount(count(States::names()));
});

it('mounts the flag dropdown over a plain select on the marketplace', function () {
    $response = $this->get(route('vendors.index'))->assertOk();

    // The island carries the flags; the select inside it is what a visitor
    // without JavaScript is left with, so both have to be in the markup.
    $response->assertSee('data-vue="ui-flag-select"', false)
        ->assertSee('img/flag/selangor.svg')
        ->assertSee('<select name="state"', false);
});

it('lists at least one area for every negeri, with no daerah twice', function () {
    foreach (States::names() as $state) {
        $districts = States::districts($state);

        expect($districts)->not->toBeEmpty("{$state} has no daerah")
            ->and($districts)->toBe(array_values(array_unique($districts)));
    }
});
