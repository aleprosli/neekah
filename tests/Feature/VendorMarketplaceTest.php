<?php

use App\Enums\VendorTier;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Support\ContactSettings;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->photography = Category::where('slug', 'photography')->first();
    $this->catering = Category::where('slug', 'catering')->first();
});

it('serves the approved vendor listing at the root url with pagination', function () {
    Vendor::factory()->count(13)->for($this->photography)->create();
    Vendor::factory()->pending()->for($this->photography)->create(['name' => 'Vendor Belum Lulus']);

    $this->get('/')
        ->assertOk()
        ->assertSee('13 vendor')
        ->assertDontSee('Vendor Belum Lulus')
        ->assertSee('page=2');
});

it('shows the demo data badge everywhere except production', function (string $environment, bool $showsBadge) {
    app()->detectEnvironment(fn (): string => $environment);

    $response = $this->get('/')->assertOk();

    $showsBadge ? $response->assertSee('Data demo') : $response->assertDontSee('Data demo');
})->with([
    'local' => ['local', true],
    'production' => ['production', false],
]);

it('moves legacy listing urls to the vendor list for good, keeping their filters', function () {
    $this->get('/marketplace')->assertMovedPermanently()->assertRedirect(route('vendors.index'));
    $this->get('/vendors')->assertMovedPermanently()->assertRedirect(route('vendors.index'));

    $this->get('/vendors?category=photography&state=Kedah')
        ->assertRedirect(route('vendors.index', ['category' => 'photography', 'state' => 'Kedah']));
});

it('lays the category tiles in a scroller with arrows, so a long list is never cut off', function () {
    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('data-scroll-track', false)
        ->assertSee('aria-label="Kategori sebelumnya"', false)
        ->assertSee('aria-label="Kategori seterusnya"', false)
        ->assertSeeInOrder(['data-scroll-track', 'Semua', 'Catering'], false);
});

it('pages a long list with a window instead of every page number', function () {
    // 12 per page, so 170 approved vendors is 15 pages. Printing all fifteen
    // numbers is what ran the pager off the side of the page.
    Vendor::factory()->count(170)->for($this->catering)->create();

    $response = $this->get(route('vendors.index', ['page' => 8]))->assertOk();

    // The window around page 8, the two ends, and the count a phone gets.
    $response->assertSee('aria-current="page">8<', false)
        ->assertSee('aria-label="Halaman 7"', false)
        ->assertSee('aria-label="Halaman 9"', false)
        ->assertSee('aria-label="Halaman 1"', false)
        ->assertSee('aria-label="Halaman 15"', false)
        ->assertSee('Halaman 8 / 15')
        ->assertDontSee('aria-label="Halaman 4"', false)
        ->assertDontSee('aria-label="Halaman 12"', false);
});

it('filters vendors by category', function () {
    Vendor::factory()->for($this->catering)->create(['name' => 'Dapur Warisan Catering']);
    Vendor::factory()->for($this->photography)->create(['name' => 'ABC Wedding Photography']);

    $this->get(route('vendors.index', ['category' => 'catering']))
        ->assertOk()
        ->assertSee('Dapur Warisan Catering')
        ->assertDontSee('ABC Wedding Photography');
});

it('searches vendors by keyword and state', function () {
    Vendor::factory()->for($this->photography)->create(['name' => 'Pelamin Warisan Kuching', 'state' => 'Sarawak']);
    Vendor::factory()->for($this->photography)->create(['name' => 'Pelamin Seri Selangor', 'state' => 'Selangor']);

    $this->get(route('vendors.index', ['q' => 'pelamin', 'state' => 'Sarawak']))
        ->assertOk()
        ->assertSee('1 vendor')
        ->assertSee('Pelamin Warisan Kuching')
        ->assertDontSee('Pelamin Seri Selangor');
});

it('searches the description, the state and the package names, not just the business name', function () {
    $byDescription = Vendor::factory()->for($this->photography)->create([
        'name' => 'Lensa Cahaya',
        'description' => 'Kami pakar gambar candid untuk majlis Melayu.',
        'state' => 'Selangor',
    ]);
    $byPackage = Vendor::factory()->for($this->photography)->create(['name' => 'Studio Kita', 'state' => 'Kedah']);
    Package::factory()->for($byPackage)->create(['name' => 'Pakej Candid Penuh', 'is_active' => true]);

    $hidden = Vendor::factory()->for($this->photography)->create(['name' => 'Jauh Sekali', 'state' => 'Johor']);
    Package::factory()->for($hidden)->create(['name' => 'Pakej Candid Penuh', 'is_active' => false]);

    $found = fn (string $keyword): array => Vendor::approved()->matching($keyword)->pluck('name')->sort()->values()->all();

    // A word from the description, and a word from a live package.
    expect($found('candid'))->toBe([$byDescription->name, $byPackage->name])
        ->and($found('Kedah'))->toBe([$byPackage->name])
        // Every word has to land somewhere, so typing more narrows the list.
        ->and($found('candid kedah'))->toBe([$byPackage->name])
        ->and($found('candid johor'))->toBe([])
        // A retired package is not something a couple can buy, so it is not searched.
        ->and($found('Jauh'))->toBe([$hidden->name]);
});

it('keeps the category and the state alongside a keyword, each one narrowing further', function () {
    Vendor::factory()->for($this->photography)->create(['name' => 'Gambar Selangor', 'state' => 'Selangor', 'description' => 'Rakaman majlis penuh.']);
    Vendor::factory()->for($this->catering)->create(['name' => 'Katering Selangor', 'state' => 'Selangor', 'description' => 'Rakaman majlis penuh.']);
    Vendor::factory()->for($this->photography)->create(['name' => 'Gambar Johor', 'state' => 'Johor', 'description' => 'Rakaman majlis penuh.']);

    $this->get(route('vendors.index', ['q' => 'rakaman']))->assertOk()->assertSee('3 vendor');

    $this->get(route('vendors.index', ['q' => 'rakaman', 'state' => 'Selangor']))
        ->assertOk()
        ->assertSee('2 vendor');

    $this->get(route('vendors.index', ['q' => 'rakaman', 'state' => 'Selangor', 'category' => 'photography']))
        ->assertOk()
        ->assertSee('1 vendor')
        ->assertSee('Gambar Selangor')
        ->assertDontSee('Katering Selangor');
});

it('offers a keyword box in the search bar, and no budget select', function () {
    // Budget left the bar: a couple knows what they want before what it costs,
    // and the price filter is still in the toolbar under the results.
    $this->get(route('vendors.index'))
        ->assertOk()
        ->assertSee('name="q"', false)
        ->assertSee('Nama vendor atau pakej')
        ->assertDontSee('Mana-mana bajet')
        ->assertSee('Mana-mana negeri');
});

it('filters vendors by price, rating and tier', function () {
    Vendor::factory()->for($this->photography)->tier(VendorTier::Recommended)->create(['name' => 'Match Vendor', 'price_from' => 1500, 'rating_avg' => 4.9, 'reviews_count' => 10]);
    Vendor::factory()->for($this->photography)->tier(VendorTier::Recommended)->create(['name' => 'Too Expensive', 'price_from' => 2800, 'rating_avg' => 4.9, 'reviews_count' => 10]);
    Vendor::factory()->for($this->photography)->tier(VendorTier::Verified)->create(['name' => 'Wrong Tier', 'price_from' => 1500, 'rating_avg' => 4.9, 'reviews_count' => 10]);

    $this->get(route('vendors.index', ['min_price' => 1000, 'max_price' => 2000, 'min_rating' => 4.8, 'tier' => 'recommended']))
        ->assertOk()
        ->assertSee('Match Vendor')
        ->assertDontSee('Too Expensive')
        ->assertDontSee('Wrong Tier');
});

it('shows an empty state when nothing matches', function () {
    $this->get(route('vendors.index', ['q' => 'tiada-vendor-begini']))
        ->assertOk()
        ->assertSee('Maaf, belum ada vendor yang sepadan');
});

it('sorts vendors by price ascending', function () {
    Vendor::factory()->for($this->photography)->create(['name' => 'Mahal Studio', 'price_from' => 3000]);
    Vendor::factory()->for($this->photography)->create(['name' => 'Murah Studio', 'price_from' => 500]);
    Vendor::factory()->for($this->photography)->create(['name' => 'Sederhana Studio', 'price_from' => 1500]);

    $this->get(route('vendors.index', ['sort' => 'price_asc']))
        ->assertOk()
        ->assertSeeInOrder(['Murah Studio', 'Sederhana Studio', 'Mahal Studio']);
});

it('shows a vendor profile with packages, reviews and related vendors', function () {
    $vendor = Vendor::factory()->for($this->photography)->create(['name' => 'ABC Wedding Photography']);
    Package::factory()->for($vendor)->create(['name' => 'Premium Package', 'price' => 2500]);
    Package::factory()->for($vendor)->create(['name' => 'Pakej Tidak Aktif', 'is_active' => false]);
    $review = Review::factory()->create(['vendor_id' => $vendor->id, 'comment' => 'Hasil kerja sangat memuaskan.']);
    Vendor::factory()->for($this->photography)->create(['name' => 'Lensa Cahaya Studio']);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee('ABC Wedding Photography')
        ->assertSee('Premium Package')
        ->assertDontSee('Pakej Tidak Aktif')
        ->assertSee('Hasil kerja sangat memuaskan.')
        ->assertSee($review->user->name)
        ->assertSee('Lensa Cahaya Studio')
        // Booking through Neekah is off: a guest is offered the vendor's own
        // line and an enquiry, never a booking form.
        ->assertSee('Log masuk untuk WhatsApp vendor')
        ->assertSee('Hantar enquiry')
        ->assertDontSee('Tempah sekarang')
        ->assertDontSee('Log masuk untuk tempah');
});

it('offers no booking form while booking through the platform is off', function () {
    $vendor = Vendor::factory()->for($this->photography)->create();
    $package = Package::factory()->for($vendor)->create();
    $customer = User::factory()->create();

    // Nothing links to it, and the endpoint itself is closed rather than
    // quietly accepting a booking nobody can reach.
    $this->actingAs($customer)
        ->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertDontSee(route('vendors.bookings.store', $vendor), false)
        ->assertSee('WhatsApp vendor');

    $this->actingAs($customer)
        ->post(route('vendors.bookings.store', $vendor), [
            'package_id' => $package->id,
            'event_date' => now()->addMonths(3)->toDateString(),
        ])
        ->assertNotFound();

    expect(Booking::count())->toBe(0);
});

it('brings the booking form back when booking is switched on', function () {
    config(['neekah.bookings_enabled' => true]);

    $vendor = Vendor::factory()->for($this->photography)->create();
    Package::factory()->for($vendor)->create();

    $this->get(route('vendors.show', $vendor))->assertOk()->assertSee('Log masuk untuk tempah');

    $customer = User::factory()->create();
    Wedding::factory()->for($customer)->create();

    $this->actingAs($customer)
        ->post(route('vendors.bookings.store', $vendor), [
            'package_id' => $vendor->packages()->value('id'),
            'event_date' => now()->addMonths(3)->toDateString(),
        ])
        ->assertRedirect();

    expect(Booking::count())->toBe(1);
});

it('shows the vendor\'s WhatsApp and phone only to a signed-in visitor', function () {
    $vendor = Vendor::factory()->for($this->photography)->create([
        'name' => 'ABC Wedding Photography',
        'phone' => '012-345 6789',
        'whatsapp' => '012-345 6789',
    ]);
    Package::factory()->for($vendor)->create();

    // A number in the markup is a number a scraper can take, signed in or not.
    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertDontSee('wa.me')
        ->assertDontSee('012-345 6789')
        ->assertSee('Log masuk untuk WhatsApp vendor');

    $this->actingAs(User::factory()->create())
        ->get(route('vendors.show', $vendor))
        ->assertSee('WhatsApp vendor')
        ->assertSee('https://wa.me/60123456789', false)
        ->assertSee('012-345 6789');
});

it('returns 404 for unknown or unapproved vendors', function () {
    $pending = Vendor::factory()->pending()->for($this->photography)->create();

    $this->get(route('vendors.show', 'vendor-tak-wujud'))->assertNotFound();
    $this->get(route('vendors.show', $pending))->assertNotFound();
});

it('draws every category filter tile with its own illustration', function () {
    Vendor::factory()->for($this->photography)->create();

    $response = $this->get('/')->assertOk();

    $response->assertSee('img/icon/all.svg')
        ->assertSee('img/icon/photography.svg')
        ->assertSee('img/icon/catering.svg');

    foreach (Category::pluck('icon') as $emoji) {
        $response->assertDontSee($emoji, escape: false);
    }
});

it('draws the category illustration on each vendor card', function () {
    Vendor::factory()->for($this->catering)->create(['name' => 'Dapur Warisan Catering']);

    $this->get('/')
        ->assertOk()
        ->assertSee('img/icon/catering.svg')
        ->assertDontSee($this->catering->icon, escape: false);
});

it('shows every vendor rank on the top left of its profile card', function () {
    foreach (VendorTier::cases() as $tier) {
        Vendor::factory()->for($this->photography)->tier($tier)->create([
            'name' => $tier->label().' Studio',
        ]);
    }

    $response = $this->get(route('vendors.index'))->assertOk();

    foreach (VendorTier::cases() as $tier) {
        $response->assertSee('data-vendor-tier="'.$tier->value.'"', false)
            ->assertSee($tier->label().' Studio');
    }

});

it('falls back to the category emoji when no illustration exists', function () {
    Category::where('slug', 'photography')->update(['slug' => 'sewa-kereta', 'icon' => '🚗']);

    $this->get('/')->assertOk()->assertSee('🚗', escape: false);
});

it('apologises for an empty search and offers to pass the request on', function () {
    app(ContactSettings::class)->save(['whatsapp' => '012-345 6789']);

    $this->get(route('vendors.index', ['q' => 'kereta kuda']))
        ->assertOk()
        ->assertSee('Maaf, belum ada vendor yang sepadan')
        ->assertSee('Kami akan kongsikan kepada rangkaian vendor kami')
        // The chat opens already saying what they were looking for.
        ->assertSee('https://wa.me/60123456789?text=', false)
        ->assertSee(rawurlencode('"kereta kuda"'), false);
});

it('leaves the WhatsApp button out of an empty search when no number is set', function () {
    $this->get(route('vendors.index', ['q' => 'kereta kuda']))
        ->assertOk()
        ->assertSee('Maaf, belum ada vendor yang sepadan')
        ->assertDontSee('WhatsApp kami');
});

it('finds a vendor under every category they work in, not only the primary one', function () {
    $studio = Vendor::factory()->for($this->photography)->create(['name' => 'Studio Dua Kerja']);
    $studio->categories()->attach($this->catering);

    Vendor::factory()->for($this->catering)->create(['name' => 'Katerer Sahaja']);

    $this->get(route('vendors.index', ['category' => 'catering']))
        ->assertOk()
        ->assertSee('Studio Dua Kerja')
        ->assertSee('Katerer Sahaja');

    $this->get(route('vendors.index', ['category' => 'photography']))
        ->assertOk()
        ->assertSee('Studio Dua Kerja')
        ->assertDontSee('Katerer Sahaja');
});

it('finds a vendor in every negeri they cover, not only where they sit', function () {
    Vendor::factory()->for($this->photography)->covering(['Johor', 'Melaka'])->create([
        'name' => 'Studio Merentas',
        'state' => 'Selangor',
    ]);

    Vendor::factory()->for($this->photography)->create(['name' => 'Studio Duduk Diam', 'state' => 'Selangor']);

    $this->get(route('vendors.index', ['state' => 'Johor']))
        ->assertOk()
        ->assertSee('Studio Merentas')
        ->assertDontSee('Studio Duduk Diam');

    $this->get(route('vendors.index', ['state' => 'Selangor']))
        ->assertOk()
        ->assertSee('Studio Merentas')
        ->assertSee('Studio Duduk Diam');
});

it('lists the extra categories and the whole service area on the public profile', function () {
    $vendor = Vendor::factory()->for($this->photography)->covering(['Melaka'])->create([
        'name' => 'Studio Merentas',
        'state' => 'Johor',
    ]);
    $vendor->categories()->attach($this->catering);

    $this->get(route('vendors.show', $vendor))
        ->assertOk()
        ->assertSee('Juga menawarkan')
        ->assertSee('Kawasan perkhidmatan')
        ->assertSee($this->catering->name)
        ->assertSeeInOrder(['Kawasan perkhidmatan', 'Johor', 'Melaka']);
});
