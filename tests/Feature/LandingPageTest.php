<?php

use App\Models\Category;
use App\Models\Vendor;
use App\Support\ContactSettings;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
});

it('renders the About page as a network for finding vendors', function () {
    $this->get('/about')
        ->assertOk()
        ->assertSee('Cari vendor kahwin')
        ->assertSee('terus berurusan dengan mereka')
        ->assertSee('Deal terus dengan vendor. Kami cuma jambatan.');
});

it('promises nothing the platform does not do while it takes no payment', function () {
    // Neekah is a network for now: no commission, no payment, no booking
    // recorded on the platform. The page used to promise all three.
    $this->get(route('landing'))
        ->assertOk()
        ->assertDontSee('Tempah & bayar di Neekah')
        ->assertDontSee('Bayaran disahkan')
        ->assertDontSee('Booking melalui platform')
        ->assertDontSee('Deposit dibayar')
        ->assertDontSee('Data contoh');
});

it('offers a WhatsApp to Neekah for a vendor that is not listed, once a number is set', function () {
    $this->get(route('landing'))->assertOk()->assertDontSee('wa.me/', false);

    app(ContactSettings::class)->save(['whatsapp' => '012-345 6789']);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Tak jumpa vendor yang anda cari?')
        ->assertSee('https://wa.me/60123456789?text=', false);
});

it('shows every active vendor category in the marketplace preview', function () {
    $response = $this->get(route('landing'));

    foreach (Category::active()->get() as $category) {
        $response->assertSee($category->name);
        $response->assertDontSee($category->icon, escape: false);
    }

    $response->assertSee('img/icon/venue.svg')->assertSee('img/icon/decoration.svg');
});

it('shows approved featured vendors and what listing gives a vendor', function () {
    $approved = Vendor::factory()->for(Category::first())->create(['name' => 'ABC Wedding Photography']);
    Vendor::factory()->pending()->for(Category::first())->create(['name' => 'Vendor Belum Lulus']);

    $this->get(route('landing'))
        ->assertSee($approved->name)
        ->assertDontSee('Vendor Belum Lulus')
        ->assertSee('Senarai percuma. Pengantin hubungi anda terus.')
        ->assertSee('Pengantin WhatsApp anda terus');
});

it('tells visitors the platform is free rather than quoting a commission', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Percuma')
        ->assertDontSee('Komisen platform')
        ->assertDontSee('8%');
});
