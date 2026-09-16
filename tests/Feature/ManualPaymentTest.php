<?php

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\PaymentRejected;
use App\Support\PaymentSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->create();
    $this->booking = Booking::factory()->for($this->customer)->for($this->vendor)->create(['total_amount' => 2500]);
});

it('stores the receipt image the couple attaches', function () {
    Storage::fake('public');

    $this->actingAs($this->customer)
        ->post(route('bookings.payments.store', $this->booking), [
            'amount' => 500,
            'paid_on' => now()->toDateString(),
            'receipt' => UploadedFile::fake()->image('resit.jpg', 1200, 900),
        ])
        ->assertRedirect();

    $payment = Payment::sole();

    expect($payment->receipt_image)->not->toBeNull()
        ->and($payment->receiptUrl())->toContain($payment->receipt_image);

    Storage::disk('public')->assertExists($payment->receipt_image);
});

it('answers the upload progress bar with the destination as JSON', function () {
    $this->actingAs($this->customer)
        ->postJson(route('bookings.payments.store', $this->booking), [
            'amount' => 500,
            'paid_on' => now()->toDateString(),
        ])
        ->assertOk()
        ->assertJson(['redirect' => route('bookings.show', $this->booking)]);
});

it('rejects a payment the vendor cannot find, and tells the couple', function () {
    Notification::fake();

    $payment = Payment::factory()->for($this->booking)->create(['amount' => 500]);

    $this->actingAs($this->vendor->user)
        ->delete(route('vendor.bookings.payments.reject', [$this->booking, $payment]))
        ->assertRedirect(route('vendor.bookings.show', $this->booking));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($this->booking->fresh()->status)->toBe(BookingStatus::PendingPayment);

    Notification::assertSentTo($this->customer, PaymentRejected::class);
});

it('keeps another vendor from verifying a payment that is not theirs', function () {
    $payment = Payment::factory()->for($this->booking)->create(['amount' => 500]);
    $other = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($other->user)
        ->post(route('vendor.bookings.payments.verify', [$this->booking, $payment]))
        ->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::AwaitingVerification);
});

it('lets the couple withdraw a record the vendor has not confirmed, but not one they have', function () {
    Storage::fake('public');

    $awaiting = Payment::factory()->for($this->booking)->create(['amount' => 500, 'receipt_image' => 'receipts/1/resit.webp']);
    Storage::disk('public')->put('receipts/1/resit.webp', 'x');

    $this->actingAs($this->customer)
        ->delete(route('bookings.payments.destroy', [$this->booking, $awaiting]))
        ->assertRedirect(route('bookings.show', $this->booking));

    expect(Payment::whereKey($awaiting->id)->exists())->toBeFalse();
    Storage::disk('public')->assertMissing('receipts/1/resit.webp');

    $verified = Payment::factory()->for($this->booking)->paid()->create(['amount' => 500]);

    $this->actingAs($this->customer)
        ->delete(route('bookings.payments.destroy', [$this->booking, $verified]))
        ->assertForbidden();
});

it('gives the vendor a way to verify each record it is waiting on', function () {
    $payment = Payment::factory()->for($this->booking)->create(['amount' => 500, 'note' => 'Transfer CIMB', 'recorded_by' => $this->customer->id]);

    $props = $this->actingAs($this->vendor->user)
        ->get(route('vendor.bookings.show', $this->booking))
        ->assertOk()
        ->viewData('props');

    $row = $props['booking']['payments'][0];

    expect($row['amount'])->toBe('RM500.00')
        ->and($row['recorded_by'])->toBe($this->customer->name)
        ->and($row['note'])->toBe('Transfer CIMB')
        ->and($row['verify_url'])->toBe(route('vendor.bookings.payments.verify', [$this->booking, $payment]))
        ->and($row['reject_url'])->toBe(route('vendor.bookings.payments.reject', [$this->booking, $payment]))
        ->and($props['booking']['outstanding'])->toBe('RM2,500.00');
});

it('hides the record form when an admin turns manual transfer off', function () {
    app(PaymentSettings::class)->save(['manual_transfer_enabled' => false]);

    $props = $this->actingAs($this->customer)->get(route('bookings.show', $this->booking))->assertOk()->viewData('props');

    expect($props['paymentForm'])->toBeNull();
});

it('lets an admin publish the bank details a couple pays into', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.settings.payments'), [
            'manual_transfer_enabled' => '1',
            'bank_name' => 'Maybank',
            'account_holder' => 'Neekah Enterprise',
            'account_number' => '512345678901',
            'instructions' => 'Bayar terus kepada vendor, kemudian rekodkan di sini.',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $props = $this->actingAs($this->customer)->get(route('bookings.show', $this->booking))->viewData('props');

    expect($props['paymentForm']['bank'])->toBe([
        'bank' => 'Maybank',
        'holder' => 'Neekah Enterprise',
        'number' => '512345678901',
    ])->and($props['paymentForm']['instructions'])->toContain('rekodkan di sini');
});
