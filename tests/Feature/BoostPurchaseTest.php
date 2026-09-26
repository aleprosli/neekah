<?php

use App\Enums\SubscriptionStatus;
use App\Models\BoostPurchase;
use App\Models\Vendor;
use App\Notifications\BoostTokensReceived;
use App\Support\BoostSettings;
use App\Support\HerepaySettings;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config()->set('services.herepay', ['base_url' => 'https://uat.herepay.org', 'secret_key' => 'neekah-secret', 'private_key' => 'neekah-private']);
    app(HerepaySettings::class)->save(['enabled' => true]);
    app(BoostSettings::class)->save(['enabled' => true]);

    $this->vendor = Vendor::factory()->create();
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 200, 'data' => ['pay_url' => 'https://uat.herepay.org/herepay/pay/BST']])]);
});

/**
 * Herepay's callback for a token pack, checksummed with Neekah's key.
 *
 * @param  array<string, string>  $fields
 */
function boostCallback(BoostPurchase $purchase, array $fields = []): TestResponse
{
    $fields = ['payment_code' => 'PAY-B', 'status_code' => '00', 'amount' => (string) $purchase->amount, ...$fields];
    $sorted = $fields;
    ksort($sorted);

    return test()->post(URL::signedRoute('webhooks.herepay.boost', ['ref' => $purchase->reference], absolute: false), [
        ...$fields,
        'checksum' => hash_hmac('sha256', implode(',', $sorted), 'neekah-private'),
    ]);
}

it('sends the vendor to pay for a pack on Neekah own Herepay account', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.boost.checkout'), ['pack' => 'large'])
        ->assertRedirect('https://uat.herepay.org/herepay/pay/BST');

    $purchase = BoostPurchase::sole();
    expect($purchase->tokens)->toBe(30)
        ->and((float) $purchase->amount)->toBe(50.0);
    Http::assertSent(fn (ClientRequest $request): bool => str_contains($request['callback_url'], '/webhooks/herepay/boost'));
});

it('keeps buying closed until an admin opens it', function () {
    app(BoostSettings::class)->save(['enabled' => false]);

    $this->actingAs($this->vendor->user)->post(route('vendor.boost.checkout'), ['pack' => 'small'])->assertNotFound();
});

it('credits the tokens on a verified callback, once however often it comes', function () {
    Notification::fake();
    $purchase = BoostPurchase::factory()->for($this->vendor)->create();

    boostCallback($purchase)->assertOk();
    boostCallback($purchase)->assertOk();

    expect($purchase->fresh()->status)->toBe(SubscriptionStatus::Paid)
        ->and($this->vendor->fresh()->boost_tokens)->toBe(10);
    Notification::assertSentToTimes($this->vendor->user, BoostTokensReceived::class, 1);
});

it('refuses a callback that paid too little or is not signed', function () {
    $purchase = BoostPurchase::factory()->for($this->vendor)->create();

    boostCallback($purchase, ['amount' => '5.00'])->assertStatus(422);
    $this->post(route('webhooks.herepay.boost', ['ref' => $purchase->reference]), ['status_code' => '00'])->assertForbidden();

    expect($this->vendor->fresh()->boost_tokens)->toBe(0);
});
