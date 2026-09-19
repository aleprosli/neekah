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

it('lets a vendor carry over a review they already had elsewhere', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.reviews.store'), [
            'author_name' => 'Puan Salmah',
            'rating' => 5,
            'comment' => 'Review sebenar yang kami terima di Google tahun lepas.',
            'written_on' => '2026-02-20',
            'photos' => [UploadedFile::fake()->image('majlis.jpg', 600, 400)],
        ])
        ->assertRedirect();

    $review = Review::sole();

    expect($review->vendor_id)->toBe($this->vendor->id)
        ->and($review->added_by)->toBe($this->vendor->user_id)
        ->and($review->isVendorAdded())->toBeTrue()
        ->and($review->created_at->toDateString())->toBe('2026-02-20')
        ->and($review->photos)->toHaveCount(1);
});

it('says on the public profile that the vendor added it', function () {
    Review::factory()->open()->create([
        'vendor_id' => $this->vendor->id,
        'added_by' => $this->vendor->user_id,
        'author_name' => 'Puan Salmah',
        'comment' => 'Review yang ditaip sendiri oleh vendor ini.',
    ]);

    // A reader has no other way to tell the business's words from a customer's.
    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('Review yang ditaip sendiri oleh vendor ini.')
        ->assertSee('Ditambah oleh vendor');
});

it('marks a review Neekah typed in differently from one the vendor did', function () {
    $admin = User::factory()->admin()->create();

    Review::factory()->open()->create([
        'vendor_id' => $this->vendor->id,
        'added_by' => $admin->id,
        'comment' => 'Review yang dipindahkan oleh pasukan Neekah.',
    ]);

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('Ditambah oleh Neekah')
        ->assertDontSee('Ditambah oleh vendor');
});

it('cannot boost the rating, the points or the ranking', function () {
    $before = $this->vendor->refresh()->only(['rating_avg', 'reviews_count', 'points_total', 'score']);

    foreach (range(1, 3) as $at) {
        $this->actingAs($this->vendor->user)->post(route('vendor.reviews.store'), [
            'author_name' => 'Pelanggan '.$at,
            'rating' => 5,
            'comment' => 'Lima bintang yang tidak sepatutnya menggerakkan apa-apa.',
        ])->assertRedirect();
    }

    expect(Review::count())->toBe(3)
        ->and($this->vendor->refresh()->only(['rating_avg', 'reviews_count', 'points_total', 'score']))->toBe($before);
});

it('can only ever land on the vendor\'s own profile', function () {
    $other = Vendor::factory()->create();

    // There is no vendor_id to send: it comes from the signed-in account.
    $this->actingAs($this->vendor->user)->post(route('vendor.reviews.store'), [
        'vendor_id' => $other->id,
        'author_name' => 'Cuba menulis pada vendor lain',
        'rating' => 5,
        'comment' => 'Ulasan yang cuba mendarat pada profil orang lain.',
    ])->assertRedirect();

    expect(Review::sole()->vendor_id)->toBe($this->vendor->id);
});

it('lets a vendor delete only what they typed in themselves', function () {
    $own = Review::factory()->open()->create([
        'vendor_id' => $this->vendor->id,
        'added_by' => $this->vendor->user_id,
    ]);
    $fromCustomer = Review::factory()->open()->create(['vendor_id' => $this->vendor->id]);
    $fromNeekah = Review::factory()->open()->create([
        'vendor_id' => $this->vendor->id,
        'added_by' => User::factory()->admin()->create()->id,
    ]);

    $this->actingAs($this->vendor->user)->delete(route('vendor.reviews.destroy', $own))->assertRedirect();
    $this->actingAs($this->vendor->user)->delete(route('vendor.reviews.destroy', $fromCustomer))->assertForbidden();
    $this->actingAs($this->vendor->user)->delete(route('vendor.reviews.destroy', $fromNeekah))->assertForbidden();

    expect(Review::whereKey([$fromCustomer->id, $fromNeekah->id])->count())->toBe(2)
        ->and(Review::find($own->id))->toBeNull();
});

it('still lets an admin take down what a vendor added', function () {
    $review = Review::factory()->open()->create([
        'vendor_id' => $this->vendor->id,
        'added_by' => $this->vendor->user_id,
    ]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.reviews.hide', $review), ['reason' => 'Tidak boleh disahkan'])->assertRedirect();
    expect($review->refresh()->isHidden())->toBeTrue();

    $this->actingAs($admin)->delete(route('admin.reviews.destroy', $review))->assertRedirect();
    expect(Review::count())->toBe(0);
});

it('keeps the form away from anyone who is not a vendor', function () {
    $payload = ['author_name' => 'X', 'rating' => 5, 'comment' => 'Ulasan yang cukup panjang.'];

    $this->post(route('vendor.reviews.store'), $payload)->assertRedirect(route('login'));
    $this->actingAs(User::factory()->create())->post(route('vendor.reviews.store'), $payload)->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->post(route('vendor.reviews.store'), $payload)->assertForbidden();

    expect(Review::count())->toBe(0);
});
