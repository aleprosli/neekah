<?php

use App\Models\Category;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Review;
use App\Models\Vendor;
use App\Models\VendorDailyStat;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    config(['services.partners.kampung1998.key' => 'kunci-ujian-kampung']);
    $this->seed(CategorySeeder::class);

    [$this->catering, $this->makeup] = Category::active()->ordered()->take(2)->get()->all();
    $this->vendor = Vendor::factory()->for($this->catering)->create(['score' => 50, 'whatsapp' => '60123456789']);
});

function partner(): object
{
    return test()->withHeaders(['X-Neekah-Key' => 'kunci-ujian-kampung', 'Accept-Language' => 'ms']);
}

it('refuses a call without the right key', function (?string $key) {
    $this->withHeaders(array_filter(['X-Neekah-Key' => $key]))
        ->getJson(route('api.partner.v1.leaderboard'))
        ->assertUnauthorized()
        ->assertJsonPath('code', 'invalid_key');
})->with([null, 'salah']);

it('stays closed while the partner has no key set', function () {
    config(['services.partners.kampung1998.key' => null]);

    $this->withHeader('X-Neekah-Key', '')->getJson(route('api.partner.v1.leaderboard'))->assertUnauthorized();
});

it('ranks approved vendors by score inside every category they work in', function () {
    $top = Vendor::factory()->for($this->catering)->create(['score' => 90]);
    $top->categories()->syncWithoutDetaching([$this->makeup->id]);
    Vendor::factory()->pending()->for($this->catering)->create(['score' => 99]);

    $response = partner()->getJson(route('api.partner.v1.leaderboard'))->assertOk();

    $catering = collect($response->json('data'))->firstWhere('slug', $this->catering->slug);
    $makeup = collect($response->json('data'))->firstWhere('slug', $this->makeup->slug);

    expect($catering['vendors_count'])->toBe(2)
        ->and(array_column($catering['vendors'], 'slug'))->toBe([$top->slug, $this->vendor->slug])
        ->and(array_column($catering['vendors'], 'rank'))->toBe([1, 2])
        ->and(array_column($makeup['vendors'], 'slug'))->toBe([$top->slug]);
});

it('gives a vendor detail with CDN images, packages, reviews and a counted WhatsApp link', function () {
    PortfolioItem::factory()->for($this->vendor)->create(['path' => 'portfolio/kek.jpg', 'is_visible' => true]);
    Package::factory()->for($this->vendor)->create(['name' => 'Pakej Emas', 'is_active' => true]);
    Review::factory()->open()->create(['vendor_id' => $this->vendor->id, 'rating' => 4]);

    $response = partner()->getJson(route('api.partner.v1.vendors.show', $this->vendor))
        ->assertOk()
        ->assertJsonPath('data.slug', $this->vendor->slug)
        ->assertJsonPath('data.packages.0.name', 'Pakej Emas')
        ->assertJsonPath('data.reviews.open.count', 1)
        ->assertJsonPath('data.reviews.verified.average', null)
        ->assertJsonPath('data.contact.phone', $this->vendor->phone);

    expect($response->json('data.gallery.0.url'))->toEndWith('portfolio/kek.jpg')
        ->and($response->json('data.reviews.latest.0'))->not->toHaveKey('author_email');

    $this->get($response->json('data.contact.whatsapp_url'))
        ->assertRedirectContains('https://wa.me/60123456789');

    expect((int) VendorDailyStat::query()->whereBelongsTo($this->vendor)->sum('whatsapp_clicks'))->toBe(1);
});

it('hides a vendor that is not approved', function () {
    $pending = Vendor::factory()->pending()->create();

    partner()->getJson(route('api.partner.v1.vendors.show', $pending))->assertNotFound();
});

it('refuses a WhatsApp link that was not signed by Neekah', function () {
    $this->get(route('api.partner.v1.vendors.whatsapp', ['vendor' => $this->vendor, 'partner' => 'kampung1998']))->assertForbidden();

    $this->get(URL::signedRoute('api.partner.v1.vendors.whatsapp', ['vendor' => $this->vendor, 'partner' => 'kampung1998']))->assertRedirect();
});

it('publishes a player review as an open review marked with the partner', function () {
    partner()->postJson(route('api.partner.v1.vendors.reviews.store', $this->vendor), [
        'rating' => 5,
        'comment' => 'Kek sedap dan sampai tepat pada masa.',
        'author_name' => 'Anak Kampung',
        'player_id' => 'pemain-1',
    ])->assertCreated()
        ->assertJsonPath('data.author_name', 'Anak Kampung')
        ->assertJsonPath('data.source', 'Melalui Kampung1998')
        ->assertJsonPath('data.verified', false);

    expect(Review::sole())->source->toBe('kampung1998')->booking_id->toBeNull()
        ->and($this->vendor->fresh()->reviews_count)->toBe(0);
});

it('rejects an incomplete review', function () {
    partner()->postJson(route('api.partner.v1.vendors.reviews.store', $this->vendor), ['rating' => 7])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['rating', 'comment', 'author_name', 'player_id']);
});

it('lets one player send five reviews an hour', function () {
    $review = [
        'rating' => 4,
        'comment' => 'Servis baik, harga berpatutan.',
        'author_name' => 'Anak Kampung',
        'player_id' => 'pemain-1',
    ];

    foreach (range(1, 5) as $ignored) {
        partner()->postJson(route('api.partner.v1.vendors.reviews.store', $this->vendor), $review)->assertCreated();
    }

    partner()->postJson(route('api.partner.v1.vendors.reviews.store', $this->vendor), $review)->assertTooManyRequests();
    partner()->postJson(route('api.partner.v1.vendors.reviews.store', $this->vendor), [...$review, 'player_id' => 'pemain-2'])->assertCreated();
});
