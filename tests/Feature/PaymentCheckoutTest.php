<?php

use App\Enums\PaymentStatus;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Models\Wedding;
use App\Support\CameraSettings;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    fakeHerepayKeys();
    fakeHerepayDirect();
    app(CameraSettings::class)->save(['enabled' => true]);

    $this->couple = User::factory()->create(['name' => 'Aina Sofea', 'phone' => '+60123456789']);
    $this->wedding = Wedding::factory()->for($this->couple)->create();
    $this->payment = Payment::factory()->kenangan()->create([
        'wedding_id' => $this->wedding->id,
        'recorded_by' => $this->couple->id,
        'expires_at' => now()->addDay(),
    ]);
});

it('shows the order and the banks that are up, on a page without the dashboard', function () {
    $response = $this->actingAs($this->couple)->get(route('payments.checkout', $this->payment))->assertOk();

    $response->assertSee(__('pages.payment_checkout.heading'))
        ->assertSee($this->payment->reference)
        ->assertSee('RM29.00')
        ->assertSee('Aina Sofea')
        ->assertSee('<meta name="page-shell" content="checkout">', false)
        ->assertDontSee('id="dashboard-drawer"', false);

    $banks = collect($response->viewData('banks'));
    expect($banks->pluck('method')->all())->toBe(['Online Banking', 'Corporate Banking'])
        ->and(collect($banks->first()['channels'])->pluck('code')->all())->toBe(['ABB0233', 'MB2U0227']);
});

it('lets only whoever started the payment confirm it', function () {
    $this->actingAs(User::factory()->create())->get(route('payments.checkout', $this->payment))->assertForbidden();
    $this->actingAs(User::factory()->create())
        ->post(route('payments.pay', $this->payment), ['payment_method' => 'Online Banking', 'bank_code' => 'MB2U0227'])
        ->assertForbidden();

    Http::assertNothingSent();
});

it('sends a payment that is settled or has run out to its status page instead', function (array $state) {
    $this->payment->update($state);

    $this->actingAs($this->couple)->get(route('payments.checkout', $this->payment))->assertRedirect(route('payments.show', $this->payment));
    $this->actingAs($this->couple)
        ->post(route('payments.pay', $this->payment), ['payment_method' => 'Online Banking', 'bank_code' => 'MB2U0227'])
        ->assertRedirect(route('payments.show', $this->payment));

    Http::assertNothingSent();
})->with([
    'paid' => [['status' => PaymentStatus::Paid, 'paid_at' => now()]],
    'expired' => [['expires_at' => now()->subMinute()]],
]);

it('still shows the order when Herepay cannot list the banks', function () {
    Http::swap(new HttpFactory);
    Http::fake(['uat.herepay.org/*' => Http::response(['status' => 'Unauthorized', 'status_code' => '41'], 401)]);

    $this->actingAs($this->couple)->get(route('payments.checkout', $this->payment))
        ->assertOk()
        ->assertSee($this->payment->reference)
        ->assertSee(__('pages.payment_checkout.banks_unavailable'));
});

it('refuses a kind of account Herepay does not take, and asks a payer without a phone for one', function () {
    $this->couple->update(['phone' => null]);

    $this->actingAs($this->couple)
        ->post(route('payments.pay', $this->payment), ['payment_method' => 'Credit Card', 'bank_code' => 'MB2U0227'])
        ->assertSessionHasErrors(['payment_method', 'phone']);

    $this->actingAs($this->couple)
        ->post(route('payments.pay', $this->payment), ['payment_method' => 'Online Banking', 'bank_code' => 'MB2U0227', 'phone' => '012-345 6789'])
        ->assertOk();

    expect($this->couple->fresh()->phone)->toBe('012-345 6789');
    Http::assertSent(fn ($request): bool => str_ends_with($request->url(), '/initiate') && $request['phone'] === '012-345 6789');
});

it('settles on a signed return Herepay posts back, and moves the payer on to the payment page', function () {
    $this->post(route('payments.return', $this->payment), herepayFields($this->payment, ['payment_code' => $this->payment->reference]))
        ->assertOk()
        ->assertSee(route('payments.show', $this->payment), false);

    $event = PaymentEvent::sole();
    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($event->type)->toBe(PaymentEvent::RETURN)
        ->and($event->verified)->toBeTrue()
        ->and($event->meta['method'])->toBe('POST');
});

it('changes nothing on a posted return with a forged checksum', function () {
    $this->post(route('payments.return', $this->payment), herepayFields($this->payment, key: 'forged'))->assertOk();

    expect($this->payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and(PaymentEvent::sole()->verified)->toBeFalse();
});
