<?php

use App\Actions\StoreOptimizedImage;
use App\Actions\SubmitReview;
use App\Models\Booking;
use App\Models\Review;
use App\Models\ReviewPhoto;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    Storage::fake('public');
    $this->admin = User::factory()->admin()->create();
    $this->vendor = Vendor::factory()->create();
});

it('takes a review off the profile without losing the record', function () {
    $review = Review::factory()->open()->create([
        'vendor_id' => $this->vendor->id,
        'comment' => 'Ulasan palsu yang ditulis oleh pesaing mereka.',
    ]);

    $this->actingAs($this->admin)
        ->post(route('admin.reviews.hide', $review), ['reason' => 'Spam dari pesaing'])
        ->assertRedirect();

    $review->refresh();

    expect($review->isHidden())->toBeTrue()
        ->and($review->hidden_by)->toBe($this->admin->id)
        ->and($review->hidden_reason)->toBe('Spam dari pesaing')
        ->and($review->comment)->toBe('Ulasan palsu yang ditulis oleh pesaing mereka.');

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertDontSee('Ulasan palsu yang ditulis oleh pesaing mereka.');
});

it('gives a hidden booking review its rating and points back when restored', function () {
    // Through the real booking flow, so the review carries the points a
    // five-star review actually earns.
    $booking = Booking::factory()->completed()->create();
    app(SubmitReview::class)->handle($booking, [
        'rating' => 5,
        'comment' => 'Majlis kami berjalan lancar sepenuhnya kerana mereka.',
    ]);

    $review = Review::sole();
    $vendor = $review->vendor;

    $ratedBefore = (float) $vendor->refresh()->rating_avg;
    $pointsBefore = $vendor->points_total;

    expect($ratedBefore)->toBe(5.0)->and($pointsBefore)->toBeGreaterThan(0);

    $this->actingAs($this->admin)->post(route('admin.reviews.hide', $review), ['reason' => 'Disputed'])->assertRedirect();

    expect((float) $vendor->refresh()->rating_avg)->toBe(0.0)
        ->and($vendor->reviews_count)->toBe(0)
        ->and($vendor->points_total)->toBeLessThan($pointsBefore);

    $this->actingAs($this->admin)->post(route('admin.reviews.restore', $review))->assertRedirect();

    expect((float) $vendor->refresh()->rating_avg)->toBe($ratedBefore)
        ->and($vendor->reviews_count)->toBe(1)
        ->and($vendor->points_total)->toBe($pointsBefore);
});

it('erases the photos too when a review is deleted for good', function () {
    $review = Review::factory()->open()->create(['vendor_id' => $this->vendor->id]);
    $photo = ReviewPhoto::factory()->for($review)->create();

    Storage::disk('public')->put($photo->path, 'x');
    Storage::disk('public')->put(StoreOptimizedImage::thumbnailPath($photo->path), 'x');

    $this->actingAs($this->admin)->delete(route('admin.reviews.destroy', $review))->assertRedirect();

    expect(Review::count())->toBe(0)
        ->and(ReviewPhoto::count())->toBe(0);

    Storage::disk('public')->assertMissing($photo->path);
    Storage::disk('public')->assertMissing(StoreOptimizedImage::thumbnailPath($photo->path));
});

it('lets an admin carry over a review the vendor already had elsewhere', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.reviews.store'), [
            'vendor_id' => $this->vendor->id,
            'author_name' => 'Puan Salmah',
            'rating' => 5,
            'comment' => 'Review ini dipindahkan dari Google oleh pasukan Neekah.',
            'written_on' => '2026-01-15',
            'photos' => [UploadedFile::fake()->image('majlis.jpg', 600, 400)],
        ])
        ->assertRedirect(route('admin.reviews.index'));

    $review = Review::sole();

    expect($review->author_name)->toBe('Puan Salmah')
        ->and($review->added_by)->toBe($this->admin->id)
        ->and($review->isVerified())->toBeFalse()
        ->and($review->created_at->toDateString())->toBe('2026-01-15')
        ->and($review->photos)->toHaveCount(1);

    // Carried over, so it must not move the ranking either.
    expect($this->vendor->refresh()->reviews_count)->toBe(0);
});

it('keeps moderation away from everyone who is not an admin', function () {
    $review = Review::factory()->open()->create(['vendor_id' => $this->vendor->id]);

    $this->post(route('admin.reviews.hide', $review))->assertRedirect(route('login'));
    $this->actingAs($this->vendor->user)->post(route('admin.reviews.hide', $review))->assertForbidden();
    $this->actingAs($this->vendor->user)->delete(route('admin.reviews.destroy', $review))->assertForbidden();
    $this->actingAs(User::factory()->create())->post(route('admin.reviews.hide', $review))->assertForbidden();

    expect($review->refresh()->isHidden())->toBeFalse();
});

it('lists reviews on its own shelves', function () {
    Review::factory()->open()->create(['vendor_id' => $this->vendor->id]);
    Review::factory()->open()->hidden()->create(['vendor_id' => $this->vendor->id]);
    Review::factory()->create();

    $countFor = fn (?string $filter): int => count($this->actingAs($this->admin)
        ->getJson(route('admin.reviews.data', array_filter(['filter' => $filter])))
        ->assertOk()
        ->json('data'));

    expect($countFor(null))->toBe(3)
        ->and($countFor('open'))->toBe(2)
        ->and($countFor('verified'))->toBe(1)
        ->and($countFor('hidden'))->toBe(1);
});
