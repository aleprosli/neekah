<?php

use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    fakeHerepayKeys();
    $this->admin = User::factory()->admin()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->create(['name' => 'Studio Aina']);
});

it('lists every kind of payment in one table, filtered by any mix of type, status, gateway and account', function () {
    $pro = Payment::factory()->pro()->paid()->create(['vendor_id' => $this->vendor->id]);
    Payment::factory()->boostPack()->create(['vendor_id' => $this->vendor->id]);
    Payment::factory()->kenangan()->create();
    Payment::factory()->for(Booking::factory()->for($this->vendor))->create();

    $all = $this->actingAs($this->admin)->getJson(route('admin.payments.data'))->assertOk();
    expect($all->json('meta.total'))->toBe(4)
        ->and($all->json('filters.purpose'))->toMatchArray(['vendor_pro' => 1, 'boost_tokens' => 1, 'kenangan' => 1, 'booking' => 1]);

    $filtered = $this->actingAs($this->admin)->getJson(route('admin.payments.data', ['purpose' => 'vendor_pro', 'status' => 'paid', 'gateway' => 'herepay', 'merchant' => 'neekah']))->assertOk();
    expect($filtered->json('meta.total'))->toBe(1)
        ->and(strip_tags($filtered->json('data.0.reference')))->toBe($pro->reference)
        ->and($filtered->json('data.0.url'))->toBe(route('admin.payments.show', $pro));

    $this->actingAs($this->admin)->getJson(route('admin.payments.data', ['search' => 'Studio Aina']))->assertJsonPath('meta.total', 3);

    // Several values of one filter widen it; values it does not know are ignored.
    $this->actingAs($this->admin)->getJson(route('admin.payments.data', ['purpose' => 'vendor_pro,boost_tokens,bogus']))->assertJsonPath('meta.total', 2);
    $this->actingAs($this->admin)->getJson(route('admin.payments.data', ['purpose' => 'vendor_pro,boost_tokens', 'status' => 'paid']))->assertJsonPath('meta.total', 1);
    $this->actingAs($this->admin)->getJson(route('admin.payments.data', ['purpose' => ['kenangan', 'booking']]))->assertJsonPath('meta.total', 2);

    $props = $this->actingAs($this->admin)->get(route('admin.payments.index', ['status' => 'paid,pending']))->viewData('props');
    expect(collect($props['table']['filters'])->firstWhere('key', 'status')['value'])->toBe(['paid', 'pending']);
});

it('shows one payment with everything that passed with its gateway', function () {
    $payment = Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id]);
    herepayCallback($payment, key: 'forged');
    herepayCallback($payment);

    $props = $this->actingAs($this->admin)->get(route('admin.payments.show', $payment))->assertOk()->viewData('props');

    expect($props['payment']['is_paid'])->toBeTrue()
        ->and(collect($props['events'])->pluck('verified')->all())->toBe([false, true])
        ->and($props['events'][1]['payload']['payment_code'])->toBe('HP-PAY-1')
        ->and($props['actions']['can_mark_paid'])->toBeFalse();
});

it('asks the gateway again from the payment page', function () {
    $payment = Payment::factory()->kenangan()->create(['gateway_reference' => 'HP-PAY-5', 'amount' => 29]);
    Http::fake(['uat.herepay.org/api/v1/herepay/transactions/HP-PAY-5' => Http::response(['status' => 200, 'data' => ['status' => 'Success', 'status_code' => '00', 'amount' => '29.00', 'payment_code' => 'HP-PAY-5']])]);

    $this->actingAs($this->admin)->post(route('admin.payments.requery', $payment))->assertRedirect()->assertSessionHas('status');

    expect($payment->fresh()->isPaid())->toBeTrue()
        ->and($payment->fresh()->album)->not->toBeNull();
});

it('ties a payment to the payment code from the gateway dashboard, then asks about it', function () {
    $payment = Payment::factory()->boostPack(tokens: 5, amount: 10)->create(['vendor_id' => $this->vendor->id]);
    Http::fake(['uat.herepay.org/api/v1/herepay/transactions/HP-PAY-77' => Http::response(['status' => 200, 'data' => ['status' => 'Pending', 'status_code' => '29', 'amount' => '10.00']])]);

    $this->actingAs($this->admin)->post(route('admin.payments.invoice', $payment), ['invoice' => 'HP-PAY-77'])->assertRedirect();
    $this->actingAs($this->admin)->post(route('admin.payments.invoice', $payment), ['invoice' => 'bad invoice; drop'])->assertSessionHasErrors('invoice');

    expect($payment->fresh()->gateway_reference)->toBe('HP-PAY-77')
        ->and($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($payment->events()->pluck('type')->all())->toBe([PaymentEvent::INVOICE_ATTACHED, PaymentEvent::REQUERY]);
});

it('settles a payment by hand, with a reason, and gives what it bought', function () {
    $payment = Payment::factory()->boostPack(tokens: 5)->create(['vendor_id' => $this->vendor->id]);

    $this->actingAs($this->admin)->post(route('admin.payments.paid', $payment), [])->assertSessionHasErrors('note');
    $this->actingAs($this->admin)->post(route('admin.payments.paid', $payment), ['note' => 'Nampak di dashboard Herepay HP-INV-9'])->assertRedirect();

    expect($payment->fresh()->isPaid())->toBeTrue()
        ->and($this->vendor->fresh()->boost_tokens)->toBe(5)
        ->and($payment->events()->sole()->user_id)->toBe($this->admin->id);

    $this->actingAs($this->admin)->post(route('admin.payments.paid', $payment), ['note' => 'Sekali lagi'])->assertNotFound();
});

it('keeps Kewangan to admins', function () {
    $payment = Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id]);

    $this->actingAs($this->vendor->user)->get(route('admin.payments.index'))->assertForbidden();
    $this->actingAs($this->vendor->user)->get(route('admin.payments.show', $payment))->assertForbidden();
    $this->actingAs($this->vendor->user)->post(route('admin.payments.paid', $payment), ['note' => 'x'])->assertForbidden();
});
