<?php

use App\Models\Category;
use App\Models\Vendor;
use App\Support\ContactSettings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\SiteTemplateSeeder;

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

it('shows three real card designs in the hero, drawn by the card renderer, with a made-up couple', function () {
    $this->seed(SiteTemplateSeeder::class);

    $response = $this->get(route('landing'))->assertOk()
        ->assertSee('Neekah Signature')
        ->assertSee('Royal Songket Gold')
        ->assertSee('Midnight Luxury')
        ->assertSee('data-vue="card-view"', false);

    // The sample couple on every card is invented: no card on neekah.my is
    // used as an example, and no real name is printed on the About page.
    $response->assertSee('Irdina')->assertSee('Danish');
});

it('says booking and payment happen with the vendor directly, in the words a couple reads', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Tempahan dan bayaran terus kepada vendor')
        ->assertSee('Tiada tempahan melalui Neekah')
        ->assertSee('bukan kepada kami');
});

it('draws its icons as line art rather than emoji', function () {
    $html = $this->get(route('landing'))->assertOk()->getContent();

    // An emoji is drawn by whichever operating system opens the page, at its own
    // weight, so a row of them never reads as one set.
    expect($html)->not->toContain('🔎')->not->toContain('📋')->not->toContain('🏆')->not->toContain('💍')
        ->and($html)->toContain('img/decor/botanical-corner.webp')
        ->and($html)->toContain('img/decor/songket-cempaka-corner.webp')
        ->and($html)->not->toContain('img/layers/corner-peony.svg')
        ->and($html)->not->toContain('img/layers/corner-filigree.svg')
        ->and($html)->not->toContain('data-icon-missing');
});

it('shows the real vendor rank badges from new through elite', function () {
    $response = $this->get(route('landing'))->assertOk();

    foreach (['new', 'verified', 'trusted', 'top', 'elite'] as $rank) {
        $response->assertSee('img/vendor-ranks/web/'.$rank.'.webp', false);
    }
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
