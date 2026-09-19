<?php

use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    Storage::fake('public');
    $this->vendor = Vendor::factory()->create();
});

it('lets a guest leave a review that appears straight away', function () {
    $this->post(route('vendors.reviews.store', $this->vendor), [
        'rating' => 5,
        'comment' => 'Servis mereka sangat baik dari mula hingga habis majlis.',
        'author_name' => 'Aina',
        'author_email' => 'aina@example.com',
    ])->assertRedirect(route('vendors.show', $this->vendor).'#review');

    $review = Review::sole();

    expect($review->rating)->toBe(5)
        ->and($review->authorName())->toBe('Aina')
        ->and($review->isVerified())->toBeFalse()
        ->and($review->isHidden())->toBeFalse();

    $this->followingRedirects()
        ->post(route('vendors.reviews.store', $this->vendor), [
            'rating' => 4,
            'comment' => 'Satu lagi ulasan untuk melihat mesej pengesahan itu.',
            'author_name' => 'Hakim',
        ])
        ->assertOk()
        // Confirmed once, beside the form, not twice on one page.
        ->assertSeeText('Terima kasih! Review anda sudah dipaparkan', false);

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('Aina')
        ->assertSee('Servis mereka sangat baik dari mula hingga habis majlis.');
});

it('signs a review with the account when someone is logged in', function () {
    $customer = User::factory()->create(['name' => 'Hakim']);

    $this->actingAs($customer)->post(route('vendors.reviews.store', $this->vendor), [
        'rating' => 4,
        'comment' => 'Gambar yang mereka hantar cantik dan tepat pada masanya.',
    ])->assertRedirect();

    $review = Review::sole();

    expect($review->user_id)->toBe($customer->id)
        ->and($review->authorName())->toBe('Hakim');
});

it('asks a guest for a name, but never asks an account holder', function () {
    $this->post(route('vendors.reviews.store', $this->vendor), [
        'rating' => 4,
        'comment' => 'Ulasan yang cukup panjang untuk lulus peraturan.',
    ])->assertSessionHasErrors('author_name', errorBag: 'review');

    $this->actingAs(User::factory()->create())
        ->post(route('vendors.reviews.store', $this->vendor), [
            'rating' => 4,
            'comment' => 'Ulasan yang cukup panjang untuk lulus peraturan.',
        ])->assertSessionHasNoErrors();
});

it('stores uploaded photos through the optimiser, thumbnail and all', function () {
    $this->post(route('vendors.reviews.store', $this->vendor), [
        'rating' => 5,
        'comment' => 'Pelamin yang mereka bina persis seperti gambar yang kami hantar.',
        'author_name' => 'Nurul',
        'photos' => [
            UploadedFile::fake()->image('satu.jpg', 800, 600),
            UploadedFile::fake()->image('dua.jpg', 800, 600),
        ],
    ])->assertRedirect();

    $photos = Review::sole()->photos;

    expect($photos)->toHaveCount(2);

    foreach ($photos as $photo) {
        // Re-encoded, never the bytes that were uploaded: the stored file is a
        // webp under reviews/, with the narrow copy beside it.
        expect($photo->path)->toStartWith('reviews/')->toEndWith('.webp');
        Storage::disk('public')->assertExists($photo->path);
        Storage::disk('public')->assertExists(str_replace('.webp', '-thumb.webp', $photo->path));
    }
});

it('refuses more photos than a review may carry', function () {
    $this->post(route('vendors.reviews.store', $this->vendor), [
        'rating' => 5,
        'comment' => 'Ulasan yang cukup panjang untuk lulus peraturan.',
        'author_name' => 'Nurul',
        'photos' => array_map(
            fn (int $at) => UploadedFile::fake()->image("gambar-{$at}.jpg", 400, 300),
            range(1, Review::MAX_PHOTOS + 1),
        ),
    ])->assertSessionHasErrors('photos', errorBag: 'review');

    expect(Review::count())->toBe(0);
});

it('will not let a vendor review their own profile', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendors.reviews.store', $this->vendor), [
            'rating' => 5,
            'comment' => 'Kami memang yang terbaik di seluruh negeri ini.',
        ])->assertSessionHasErrors('comment', errorBag: 'review');

    expect(Review::count())->toBe(0);
});

it('keeps an open review out of the rating, the count and the ranking', function () {
    $before = $this->vendor->refresh()->only(['rating_avg', 'reviews_count', 'points_total', 'score']);

    $this->post(route('vendors.reviews.store', $this->vendor), [
        'rating' => 5,
        'comment' => 'Ulasan lima bintang yang tidak sepatutnya menggerakkan ranking.',
        'author_name' => 'Seseorang',
    ])->assertRedirect();

    expect($this->vendor->refresh()->only(['rating_avg', 'reviews_count', 'points_total', 'score']))->toBe($before);
});

it('shows the two averages apart, so neither stands for the other', function () {
    Review::factory()->open()->count(2)->create(['vendor_id' => $this->vendor->id, 'rating' => 3]);

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('2 review terbuka')
        ->assertSee('Review terbuka');
});

it('never shows a hidden review on the profile', function () {
    Review::factory()->open()->hidden()->create([
        'vendor_id' => $this->vendor->id,
        'comment' => 'Ulasan ini sudah dibuang oleh admin kerana spam.',
    ]);

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertDontSee('Ulasan ini sudah dibuang oleh admin kerana spam.');
});

it('refuses a review on a vendor the marketplace does not show', function () {
    $pending = Vendor::factory()->pending()->create();

    $this->post(route('vendors.reviews.store', $pending), [
        'rating' => 5,
        'comment' => 'Ulasan yang cukup panjang untuk lulus peraturan.',
        'author_name' => 'Seseorang',
    ])->assertNotFound();
});
