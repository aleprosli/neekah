<?php

use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Models\Vendor;
use App\Support\Herepay\HerepayGateway;
use App\Support\HerepaySettings;
use App\Support\ProSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    config()->set('services.herepay', [
        'base_url' => 'https://uat.herepay.org',
        'secret_key' => 'test-secret',
        'private_key' => 'test-private',
        'api_key' => 'test-api-key',
    ]);
    app(HerepaySettings::class)->save(['enabled' => true]);
    app(ProSettings::class)->save(['enabled' => true, 'monthly_price' => 49, 'yearly_price' => 490]);

    $this->owner = User::factory()->vendor()->create(['email' => 'studio@example.com', 'phone' => '+60123456789']);
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create();
    $this->payment = fn (array $attributes = []): Payment => Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id, 'amount' => 49, ...$attributes]);
});

it('creates a single-use payment link, sends the vendor to it and keeps what was asked and answered', function () {
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 200, 'data' => ['pay_url' => 'https://uat.herepay.org/herepay/pay/ABC']])]);

    $this->actingAs($this->owner)
        ->post(route('vendor.pro.checkout'), ['plan' => 'monthly'])
        ->assertRedirect('https://uat.herepay.org/herepay/pay/ABC');

    $payment = Payment::sole();

    Http::assertSent(fn (ClientRequest $request): bool => $request->url() === 'https://uat.herepay.org/api/integration/create-payment-link'
        && $request->hasHeader('SecretKey', 'test-secret')
        && $request['amount'] === 49.0
        && $request['usage_type'] === 'single'
        && $request['payer_email'] === 'studio@example.com'
        && $request['redirect_url'] === route('payments.return', $payment)
        && str_contains($request['callback_url'], '/webhooks/herepay?ref='.$payment->reference)
        && str_contains($request['callback_url'], 'signature='));

    expect($payment->payment_url)->toBe('https://uat.herepay.org/herepay/pay/ABC')
        ->and($payment->merchant)->toBe('neekah')
        ->and($payment->gateway)->toBe('herepay')
        ->and($payment->events()->sole()->type)->toBe(PaymentEvent::LINK_CREATED)
        ->and($payment->events()->sole()->payload['response']['data']['pay_url'])->toBe('https://uat.herepay.org/herepay/pay/ABC');
});

it('marks the payment failed and keeps the refusal when Herepay will not make the link', function () {
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 'Unauthorized', 'status_code' => '41'], 401)]);

    $this->actingAs($this->owner)
        ->from(route('vendor.pro.index'))
        ->post(route('vendor.pro.checkout'), ['plan' => 'monthly'])
        ->assertRedirect(route('vendor.pro.index'))
        ->assertSessionHasErrors('plan');

    $payment = Payment::sole();
    expect($payment->status)->toBe(PaymentStatus::Failed)
        ->and($payment->events()->sole()->type)->toBe(PaymentEvent::LINK_FAILED);
});

it('settles on a callback whose checksum matches, and keeps the callback as it came', function () {
    $payment = ($this->payment)();

    herepayCallback($payment)->assertOk();
    herepayCallback($payment)->assertOk();

    $payment->refresh();
    $events = $payment->events()->where('type', PaymentEvent::CALLBACK)->get();

    expect($payment->status)->toBe(PaymentStatus::Paid)
        ->and($payment->gateway_reference)->toBe('HP-PAY-1')
        ->and($payment->gateway_invoice)->toBe('HP-INV-1')
        ->and($payment->gateway_transaction_id)->toBe('2609262114370348')
        ->and($payment->method)->toBe('FPX')
        ->and($this->vendor->fresh()->isPro())->toBeTrue()
        ->and($events)->toHaveCount(2)
        ->and($events->pluck('outcome')->all())->toBe(['paid', 'already_paid'])
        ->and($events->first()->verified)->toBeTrue()
        ->and($events->first()->payload['payment_code'])->toBe('HP-PAY-1')
        ->and($payment->gateway_payload)->toMatchArray(['source' => 'callback', 'verified' => true, 'outcome' => 'already_paid'])
        ->and($payment->gateway_payload['data']['reference_code'])->toBe('HP-INV-1');
});

it('refuses a callback signed with the wrong key, and still keeps it', function () {
    $payment = ($this->payment)();

    herepayCallback($payment, key: 'someone-else')->assertForbidden();

    $event = PaymentEvent::sole();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($event->payment_id)->toBe($payment->id)
        ->and($event->verified)->toBeFalse()
        ->and($event->http_status)->toBe(403)
        ->and($payment->fresh()->gateway_payload)->toMatchArray(['source' => 'callback', 'verified' => false])
        ->and($payment->fresh()->gateway_payload['data']['payment_code'])->toBe('HP-PAY-1')
        // Kept to read, never trusted: no code is taken from it.
        ->and($payment->fresh()->gateway_reference)->toBeNull();
});

it('refuses a callback whose reference was swapped for another payment', function () {
    $mine = ($this->payment)();
    $theirs = Payment::factory()->pro()->create(['amount' => 49]);

    $url = URL::signedRoute('payments.webhook', ['gateway' => 'herepay', 'ref' => $mine->reference], absolute: false);

    $this->post(str_replace($mine->reference, $theirs->reference, $url), herepayFields($theirs))->assertForbidden();

    expect($theirs->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(PaymentEvent::sole()->payment_id)->toBeNull();
});

it('keeps the invoice of a payment still settling, so it can be asked about later', function () {
    $payment = ($this->payment)();

    herepayCallback($payment, ['status' => 'Pending', 'status_code' => '29'])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($payment->fresh()->gateway_invoice)->toBe('HP-INV-1')
        ->and($this->vendor->fresh()->isPro())->toBeFalse();
});

it('does not settle when less than the price was paid', function () {
    $payment = ($this->payment)(['amount' => 490]);

    herepayCallback($payment, ['amount' => '1.00'])->assertStatus(422);

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($this->vendor->fresh()->isPro())->toBeFalse()
        ->and(PaymentEvent::sole()->outcome)->toBe('amount_mismatch');
});

it('settles from the payer\'s signed return when the callback never came', function () {
    $payment = ($this->payment)();

    $this->actingAs($this->owner)
        ->get(route('payments.return', [$payment, ...herepayFields($payment)]))
        ->assertRedirect(route('payments.show', $payment));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($this->vendor->fresh()->isPro())->toBeTrue()
        ->and(PaymentEvent::sole()->type)->toBe(PaymentEvent::RETURN);
});

it('changes nothing on a return it cannot verify', function () {
    $payment = ($this->payment)();

    $this->actingAs($this->owner)
        ->get(route('payments.return', [$payment, ...herepayFields($payment, key: 'forged')]))
        ->assertRedirect(route('payments.show', $payment));

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(PaymentEvent::sole()->verified)->toBeFalse();
});

it('asks Herepay again, by its payment code, about a payment whose callback was lost, and settles it', function () {
    // Herepay's lookup finds a payment by payment_code (HP-PAY-…), not by the invoice its docs name.
    $payment = ($this->payment)(['gateway_reference' => 'HP-PAY-9', 'gateway_invoice' => 'HP-INV-9', 'created_at' => now()->subMinutes(20)]);
    Http::fake(['uat.herepay.org/api/v1/herepay/transactions/HP-PAY-9' => Http::response(['status' => 200, 'data' => [
        'status' => 'Completed', 'status_code' => '1', 'amount' => '49.00', 'reference_code' => 'HP-INV-9', 'payment_code' => 'PGW-9', 'fpx_transaction_id' => '999',
    ]])]);

    Artisan::call('neekah:requery-payments');

    Http::assertSent(fn (ClientRequest $request): bool => str_ends_with($request->url(), '/transactions/HP-PAY-9')
        && $request->hasHeader('SecretKey', 'test-secret') && $request->hasHeader('XApiKey', 'test-api-key'));
    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->last_checked_at)->not->toBeNull()
        ->and($this->vendor->fresh()->isPro())->toBeTrue()
        ->and(PaymentEvent::sole()->type)->toBe(PaymentEvent::REQUERY);
});

it('does not ask Herepay without an API key, and closes a link long past its expiry', function () {
    config()->set('services.herepay.api_key', null);
    Http::fake();
    $payment = ($this->payment)(['gateway_invoice' => 'HP-INV-9', 'created_at' => now()->subMinutes(20), 'expires_at' => now()->subHours(2)]);

    Artisan::call('neekah:requery-payments');

    Http::assertNothingSent();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Expired);
});

it('still takes callbacks at the addresses links were made with before', function () {
    $payment = Payment::factory()->kenangan()->create();
    // What the old signedRoute('webhooks.herepay.camera', ['ref' => ...]) produced.
    $path = '/webhooks/herepay/kamera?ref='.$payment->reference;
    $signed = $path.'&signature='.hash_hmac('sha256', $path, config('app.key'));

    $this->post($signed, herepayFields($payment))->assertOk();

    expect($payment->fresh()->isPaid())->toBeTrue()
        ->and($payment->fresh()->album)->not->toBeNull();
});

it('keeps checkout closed while an admin has Herepay switched off', function () {
    app(HerepaySettings::class)->save(['enabled' => false]);

    expect(app(HerepayGateway::class)->isConfigured())->toBeFalse();

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

    expect(app(HerepayGateway::class)->isConfigured())->toBeTrue();
});

it('keeps the Herepay switch away from anyone who is not an admin', function () {
    $this->actingAs($this->owner)->put(route('admin.settings.herepay'), ['enabled' => '0'])->assertForbidden();
});
