<?php

it('renders the promotional landing page at /about', function () {
    $response = $this->get('/about');

    $response->assertOk()
        ->assertSee('Semua Urusan Majlis')
        ->assertSee('Satu Platform');
});

it('shows every vendor category in the marketplace preview', function () {
    $response = $this->get(route('landing'));

    foreach (['Catering', 'Pelamin', 'Decoration', 'Photography', 'Videography', 'Emcee', 'Makeup', 'Bridal', 'Venue', 'Wedding Cake', 'Entertainment', 'Invitation'] as $category) {
        $response->assertSee($category);
    }
});

it('shows placeholder featured vendors and the vendor point system', function () {
    $this->get(route('landing'))
        ->assertSee('ABC Wedding Photography')
        ->assertSee('Recommended Vendor')
        ->assertSee('Booking melalui platform')
        ->assertSee('+100');
});
