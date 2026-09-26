<?php

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\Vendor;
use App\Models\VendorBoostEntry;
use App\Notifications\BoostTokensReceived;
use App\Support\BoostSettings;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    fakeHerepayKeys();
    app(BoostSettings::class)->save(['enabled' => true]);

    $this->vendor = Vendor::factory()->create();
    fakeHerepayLink('https://uat.herepay.org/herepay/pay/BST');
});

it('sends the vendor to pay for a pack on Neekah own Herepay account', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.boost.checkout'), ['pack' => 'large'])
        ->assertRedirect('https://uat.herepay.org/herepay/pay/BST');

    $payment = Payment::sole();
    expect($payment->purpose)->toBe(PaymentPurpose::BoostTokens)
        ->and($payment->merchant)->toBe('neekah')
        ->and($payment->detail('tokens'))->toBe(30)
        ->and((float) $payment->amount)->toBe(50.0);
    Http::assertSent(fn (ClientRequest $request): bool => str_contains($request['callback_url'], '/webhooks/herepay?ref='.$payment->reference));
});

it('keeps buying closed until an admin opens it', function () {
    app(BoostSettings::class)->save(['enabled' => false]);

    $this->actingAs($this->vendor->user)->post(route('vendor.boost.checkout'), ['pack' => 'small'])->assertNotFound();
});

it('credits the tokens on a verified callback, once however often it comes, with the payment as their source', function () {
    Notification::fake();
    $payment = Payment::factory()->boostPack(tokens: 10)->create(['vendor_id' => $this->vendor->id]);

    herepayCallback($payment)->assertOk();
    herepayCallback($payment)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($this->vendor->fresh()->boost_tokens)->toBe(10)
        ->and(VendorBoostEntry::sole()->source->is($payment))->toBeTrue();
    Notification::assertSentToTimes($this->vendor->user, BoostTokensReceived::class, 1);
});

it('refuses a callback that paid too little or is not signed', function () {
    $payment = Payment::factory()->boostPack(amount: 10)->create(['vendor_id' => $this->vendor->id]);

    herepayCallback($payment, ['amount' => '5.00'])->assertStatus(422);
    $this->post(route('payments.webhook', ['gateway' => 'herepay', 'ref' => $payment->reference]), ['status_code' => '00'])->assertForbidden();

    expect($this->vendor->fresh()->boost_tokens)->toBe(0);
});
