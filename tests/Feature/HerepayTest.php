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

it('starts the payment with the chosen bank, signed, and posts the vendor on to FPX', function () {
    fakeHerepayDirect();

    $response = $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'monthly']);
    $payment = Payment::sole();
    $response->assertRedirect(route('payments.checkout', $payment));

    $this->actingAs($this->owner)
        ->post(route('payments.pay', $payment), ['payment_method' => 'Online Banking', 'bank_code' => 'MB2U0227'])
        ->assertOk()
        ->assertSee('action="https://uat.mepsfpx.com.my/FPXMain/seller2DReceiver.jsp"', false)
        ->assertSee('name="fpx_checkSum" value="ABC123"', false);

    Http::assertSent(function (ClientRequest $request) use ($payment): bool {
        $fields = $request->data();

        return $request->url() === 'https://uat.herepay.org/api/v1/herepay/initiate'
            && $request->isForm()
            && $request->hasHeader('SecretKey', 'test-secret')
            && $request->hasHeader('XApiKey', 'test-api-key')
            && $fields['amount'] === '49.00'
            && $fields['payment_code'] === $payment->reference
            && $fields['payment_method'] === 'Online Banking'
            && $fields['bank_prefix'] === 'MB2U0227'
            && $fields['email'] === 'studio@example.com'
            && $fields['phone'] === '+60123456789'
            && $fields['redirect_url'] === route('payments.return', $payment)
            && str_contains($fields['callback_url'], '/webhooks/herepay?ref='.$payment->reference)
            && str_contains($fields['callback_url'], 'signature=')
            && $fields['checksum'] === hash_hmac('sha256', collect($fields)->except('checksum')->sortKeys()->implode(','), 'test-private');
    });

    $payment->refresh();
    expect($payment->merchant)->toBe('neekah')
        ->and($payment->gateway)->toBe('herepay')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->gateway_reference)->toBe($payment->reference)
        ->and($payment->events()->sole()->type)->toBe(PaymentEvent::INITIATED)
        ->and($payment->events()->sole()->payload['request']['bank'])->toBe('MB2U0227');
});

it('keeps the payment open and the refusal when Herepay will not start it', function () {
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 'Unauthorized', 'status_code' => '41', 'error' => 'Checksum verification failed.'], 401)]);
    $payment = ($this->payment)(['recorded_by' => $this->owner->id]);

    $this->actingAs($this->owner)
        ->from(route('payments.checkout', $payment))
        ->post(route('payments.pay', $payment), ['payment_method' => 'Online Banking', 'bank_code' => 'MB2U0227'])
        ->assertRedirect(route('payments.checkout', $payment))
        ->assertSessionHasErrors(['bank_code' => __('flash.payment.initiate_failed')]);

    $payment->refresh();
    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->gateway_reference)->toBeNull()
        ->and($payment->events()->sole()->type)->toBe(PaymentEvent::INITIATE_FAILED)
        ->and($payment->events()->sole()->outcome)->toBe('failed');
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
