<?php

use App\Enums\VendorTier;
use App\Models\Category;
use App\Models\Package;
use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
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
        ->assertSee('Log masuk untuk tempah');
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
