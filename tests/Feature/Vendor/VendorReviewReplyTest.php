<?php

use App\Models\Review;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->create();
    $this->review = Review::factory()->open()->create([
        'vendor_id' => $this->vendor->id,
        'comment' => 'Mereka lambat sampai pada hari majlis kami.',
    ]);
});

it('lets a vendor answer a review, and shows the answer on the profile', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.reviews.reply', $this->review), ['reply' => 'Maaf atas kelewatan itu. Kami sudah perbaiki jadual pasukan kami.'])
        ->assertRedirect();

    expect($this->review->refresh()->hasReply())->toBeTrue();

    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('Maaf atas kelewatan itu. Kami sudah perbaiki jadual pasukan kami.');
});

it('lets a vendor report a review without it leaving the profile', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.reviews.report', $this->review), ['reason' => 'Kami tidak pernah menerima tempahan daripada orang ini.'])
        ->assertRedirect();

    expect($this->review->refresh()->isReported())->toBeTrue()
        ->and($this->review->isHidden())->toBeFalse();

    // Reporting is asking, not removing: the review is still on the page.
    $this->get(route('vendors.show', $this->vendor))
        ->assertOk()
        ->assertSee('Mereka lambat sampai pada hari majlis kami.');
});

it('gives a vendor no way to remove a review at all', function () {
    $this->actingAs($this->vendor->user)
        ->delete(route('admin.reviews.destroy', $this->review))
        ->assertForbidden();

    $this->actingAs($this->vendor->user)
        ->post(route('admin.reviews.hide', $this->review))
        ->assertForbidden();

    expect(Review::count())->toBe(1)
        ->and($this->review->refresh()->isHidden())->toBeFalse();
});

it('will not let one vendor answer another vendor\'s review', function () {
    $other = Vendor::factory()->create();

    $this->actingAs($other->user)
        ->post(route('vendor.reviews.reply', $this->review), ['reply' => 'Cuba menulis pada review orang lain.'])
        ->assertForbidden();

    $this->actingAs($other->user)
        ->post(route('vendor.reviews.report', $this->review), ['reason' => 'Cuba melaporkan review orang lain.'])
        ->assertForbidden();

    expect($this->review->refresh()->hasReply())->toBeFalse()
        ->and($this->review->isReported())->toBeFalse();
});

it('shows a vendor their own reviews, including the ones an admin took down', function () {
    Review::factory()->open()->hidden()->create([
        'vendor_id' => $this->vendor->id,
        'comment' => 'Ulasan yang sudah ditarik admin.',
    ]);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.reviews.index'))
        ->assertOk()
        ->assertSee('Mereka lambat sampai pada hari majlis kami.')
        ->assertSee('Ulasan yang sudah ditarik admin.')
        ->assertSee('sudah ditarik oleh admin', false);
});

it('keeps the vendor review page away from couples and admins', function () {
    $this->actingAs(User::factory()->create())->get(route('vendor.reviews.index'))->assertForbidden();
    $this->actingAs(User::factory()->admin()->create())->get(route('vendor.reviews.index'))->assertForbidden();
});
