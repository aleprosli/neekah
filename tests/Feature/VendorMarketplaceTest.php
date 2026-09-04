<?php

it('lists demo vendors with pagination', function () {
    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('18 vendor')
        ->assertSee('ABC Wedding Photography')
        ->assertSee('page=2');
});

it('serves the vendor listing at the root url', function () {
    $this->get('/')->assertOk()->assertSee('ABC Wedding Photography');
});

it('redirects legacy listing urls to the root', function () {
    $this->get('/marketplace')->assertRedirect('/');
    $this->get('/vendors')->assertRedirect('/');
});

it('filters vendors by category', function () {
    $this->get(route('vendors.index', ['category' => 'catering']))
        ->assertOk()
        ->assertSee('Dapur Warisan Catering')
        ->assertSee('Rasa Sayang Catering')
        ->assertDontSee('ABC Wedding Photography');
});

it('searches vendors by keyword and state', function () {
    $this->get(route('vendors.index', ['q' => 'pelamin', 'state' => 'Sarawak']))
        ->assertOk()
        ->assertSee('1 vendor')
        ->assertSee('Pelamin Warisan Kuching');
});

it('filters vendors by price, rating and tier', function () {
    $this->get(route('vendors.index', ['min_price' => 1000, 'max_price' => 2000, 'min_rating' => 4.8, 'tier' => 'Recommended']))
        ->assertOk()
        ->assertSee('ABC Wedding Photography')
        ->assertDontSee('Seri Pelamin Studio');
});

it('shows an empty state when nothing matches', function () {
    $this->get(route('vendors.index', ['q' => 'tiada-vendor-begini']))
        ->assertOk()
        ->assertSee('Tiada vendor sepadan');
});

it('sorts vendors by price ascending', function () {
    $response = $this->get(route('vendors.index', ['sort' => 'price_asc']));

    $response->assertOk()->assertSeeInOrder(['Dapur Warisan Catering', 'Kad Kita Digital', 'Sweet Layers Cakery']);
});

it('shows a vendor profile with packages and booking form', function () {
    $this->get(route('vendors.show', 'abc-wedding-photography'))
        ->assertOk()
        ->assertSee('ABC Wedding Photography')
        ->assertSee('Premium Package')
        ->assertSee('Tempah sekarang')
        ->assertSee('Lensa Cahaya Studio');
});

it('returns 404 for an unknown vendor', function () {
    $this->get(route('vendors.show', 'vendor-tak-wujud'))->assertNotFound();
});

it('records a demo booking and flashes a confirmation', function () {
    $this->post(route('vendors.book', 'abc-wedding-photography'), [
        'package' => 1,
        'event_date' => now()->addMonths(3)->toDateString(),
        'name' => 'Aina & Hakim',
        'phone' => '0123456789',
    ])
        ->assertRedirect(route('vendors.show', 'abc-wedding-photography'))
        ->assertSessionHas('booking.package', 'Premium Package')
        ->assertSessionHas('booking.deposit', 1000);

    $this->get(route('vendors.show', 'abc-wedding-photography'))
        ->assertSee('Booking demo direkod')
        ->assertSee('Pending Payment');
});

it('rejects a booking with a past date or invalid package', function () {
    $this->from(route('vendors.show', 'abc-wedding-photography'))
        ->post(route('vendors.book', 'abc-wedding-photography'), [
            'package' => 5,
            'event_date' => now()->subDay()->toDateString(),
            'name' => '',
            'phone' => '',
        ])
        ->assertRedirect(route('vendors.show', 'abc-wedding-photography'))
        ->assertSessionHasErrors(['package', 'event_date', 'name', 'phone']);
});
