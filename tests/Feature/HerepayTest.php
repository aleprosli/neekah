<?php

use App\Enums\SubscriptionStatus;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Support\Herepay\HerepayClient;
use App\Support\HerepaySettings;
use App\Support\ProSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    config()->set('services.herepay', [
        'base_url' => 'https://uat.herepay.org',
        'secret_key' => 'test-secret',
        'private_key' => 'test-private',
    ]);
    app(HerepaySettings::class)->save(['enabled' => true]);
    app(ProSettings::class)->save(['enabled' => true, 'monthly_price' => 49, 'yearly_price' => 490]);

    $this->owner = User::factory()->vendor()->create(['email' => 'studio@example.com', 'phone' => '+60123456789']);
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create();
});

/**
 * Post a callback the way Herepay does: form fields plus the checksum over them,
 * to the signed callback URL the link was created with.
 *
 * @param  array<string, string>  $fields
 */
function herepayCallback(VendorSubscription $subscription, array $fields, ?string $checksum = null): TestResponse
{
    $fields = [
        'reference_code' => 'INV-1',
        'payment_code' => 'PAY-1',
        'transaction_id' => '',
        'status' => 'Success',
        'status_code' => '00',
        'message' => 'Approved',
        'amount' => (string) $subscription->amount,
        'currency' => 'MYR',
        'payment_method' => 'FPX',
        'fpx_type' => '',
        'bank_name' => 'Maybank',
        ...$fields,
    ];

    $sorted = $fields;
    ksort($sorted);

    return test()->post(URL::signedRoute('webhooks.herepay', ['ref' => $subscription->reference], absolute: false), [
        ...$fields,
        'checksum' => $checksum ?? hash_hmac('sha256', implode(',', $sorted), 'test-private'),
    ]);
}

it('creates a single-use payment link and sends the vendor to it', function () {
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 200, 'data' => ['pay_url' => 'https://uat.herepay.org/herepay/pay/ABC']])]);

    $this->actingAs($this->owner)
        ->post(route('vendor.pro.checkout'), ['plan' => 'monthly'])
        ->assertRedirect('https://uat.herepay.org/herepay/pay/ABC');

    $subscription = VendorSubscription::sole();

    Http::assertSent(function (ClientRequest $request) use ($subscription): bool {
        return $request->url() === 'https://uat.herepay.org/api/integration/create-payment-link'
            && $request->hasHeader('SecretKey', 'test-secret')
            && $request['amount'] === 49.0
            && $request['usage_type'] === 'single'
            && $request['payer_email'] === 'studio@example.com'
            && $request['redirect_url'] === route('vendor.pro.done', ['ref' => $subscription->reference])
            && str_contains($request['callback_url'], 'ref='.$subscription->reference)
            && str_contains($request['callback_url'], 'signature=');
    });

    expect($subscription->payment_url)->toBe('https://uat.herepay.org/herepay/pay/ABC');
});

it('sends the vendor back with an error when Herepay refuses the link', function () {
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 'Unauthorized', 'status_code' => '41'], 401)]);

    $this->actingAs($this->owner)
        ->from(route('vendor.pro.index'))
        ->post(route('vendor.pro.checkout'), ['plan' => 'monthly'])
        ->assertRedirect(route('vendor.pro.index'))
        ->assertSessionHasErrors('plan');

    expect(VendorSubscription::sole()->status)->toBe(SubscriptionStatus::Failed);
});

it('activates Pro on a callback whose checksum matches', function () {
    $subscription = VendorSubscription::factory()->for($this->vendor)->create(['amount' => 49]);

    herepayCallback($subscription, [])->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Paid)
        ->and($subscription->fresh()->gateway_reference)->toBe('PAY-1')
        ->and($this->vendor->fresh()->isPro())->toBeTrue();
});

it('refuses a callback with a wrong checksum', function () {
    $subscription = VendorSubscription::factory()->for($this->vendor)->create(['amount' => 49]);

    herepayCallback($subscription, [], checksum: str_repeat('0', 64))->assertForbidden();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Pending);
});

it('refuses a callback whose reference was swapped for another purchase', function () {
    $mine = VendorSubscription::factory()->for($this->vendor)->create(['amount' => 49]);
    $theirs = VendorSubscription::factory()->for(Vendor::factory()->for(Category::first()))->create(['amount' => 49]);

    $url = URL::signedRoute('webhooks.herepay', ['ref' => $mine->reference], absolute: false);
    $fields = ['status_code' => '00', 'amount' => '49.00'];
    ksort($fields);

    $this->post(str_replace($mine->reference, $theirs->reference, $url), [...$fields, 'checksum' => hash_hmac('sha256', implode(',', $fields), 'test-private')])
        ->assertForbidden();

    expect($theirs->fresh()->status)->toBe(SubscriptionStatus::Pending);
});

it('leaves a purchase pending while Herepay is still settling it', function () {
    $subscription = VendorSubscription::factory()->for($this->vendor)->create(['amount' => 49]);

    herepayCallback($subscription, ['status' => 'Pending', 'status_code' => '29'])->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Pending)
        ->and($this->vendor->fresh()->isPro())->toBeFalse();
});

it('does not activate Pro when less than the price was paid', function () {
    $subscription = VendorSubscription::factory()->for($this->vendor)->create(['amount' => 490]);

    herepayCallback($subscription, ['amount' => '1.00'])->assertStatus(422);

    expect($this->vendor->fresh()->isPro())->toBeFalse();
});

it('keeps checkout closed while an admin has Herepay switched off', function () {
    app(HerepaySettings::class)->save(['enabled' => false]);

    expect(app(HerepayClient::class)->isConfigured())->toBeFalse();

    $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'monthly'])->assertNotFound();
});

it('will not let an admin switch Herepay on while a key is missing from .env', function () {
    config()->set('services.herepay.private_key', null);
    app(HerepaySettings::class)->save(['enabled' => false]);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.herepay'), ['enabled' => '1'])
        ->assertSessionHasErrors(['enabled' => __('validation.custom.herepay_keys_missing', ['keys' => 'HEREPAY_PRIVATE_KEY'])]);

    expect(app(HerepaySettings::class)->isEnabled())->toBeFalse();

    config()->set('services.herepay.private_key', 'test-private');

    $this->actingAs($admin)->put(route('admin.settings.herepay'), ['enabled' => '1'])->assertSessionHasNoErrors();

    expect(app(HerepayClient::class)->isConfigured())->toBeTrue();
});

it('keeps the Herepay switch away from anyone who is not an admin', function () {
    $this->actingAs($this->owner)->put(route('admin.settings.herepay'), ['enabled' => '0'])->assertForbidden();
});
