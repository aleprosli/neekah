<?php

use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('ships no analytics tag when no measurement id is configured', function () {
    config(['services.google_analytics.measurement_id' => null]);

    $this->get(route('landing'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false)
        ->assertDontSee('gtag(', false);
});

it('loads the google tag once the measurement id is set', function () {
    config(['services.google_analytics.measurement_id' => 'G-ABC1234XYZ']);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('https://www.googletagmanager.com/gtag/js?id=G-ABC1234XYZ', false)
        ->assertSee('gtag(\'config\', "G-ABC1234XYZ")', false);
});

it('counts the page swaps that never fire a page load', function () {
    config(['services.google_analytics.measurement_id' => 'G-ABC1234XYZ']);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('neekah:navigated', false)
        ->assertSee("gtag('event', 'page_view'", false);

    // The other half of the same contract: navigation.js announces the swap.
    expect(file_get_contents(resource_path('js/navigation.js')))
        ->toContain("new CustomEvent('neekah:navigated'");
});

it('refuses to print anything that is not a google tag id', function (string $configured) {
    config(['services.google_analytics.measurement_id' => $configured]);

    $this->get(route('landing'))
        ->assertOk()
        ->assertDontSee('googletagmanager.com', false);
})->with([
    'a script closing the tag' => ['G-1</script><script>alert(1)</script>'],
    'a quote breaking out of the string' => ['G-1"; alert(1); //'],
    'an unrelated value' => ['not-an-id'],
    'empty' => [''],
]);

it('reaches the invitation cards too, since they render in the same shell', function () {
    config(['services.google_analytics.measurement_id' => 'G-ABC1234XYZ']);

    $this->get(route('sites.templates'))
        ->assertOk()
        ->assertSee('googletagmanager.com', false);
});
