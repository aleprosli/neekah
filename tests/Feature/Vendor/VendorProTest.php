<?php

use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VendorPlan;
use App\Models\Category;
use App\Models\Payment;
use App\Models\PaymentEvent;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\ProActivated;
use App\Notifications\ProExpiring;
use App\Support\ProSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->owner = User::factory()->vendor()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create();
    app(ProSettings::class)->save(['enabled' => true, 'monthly_price' => 49, 'yearly_price' => 490]);
    fakeHerepayKeys();
});

it('sends the vendor to a payment link for the plan they picked, at todays price', function () {
    fakeHerepayLink('https://uat.herepay.org/herepay/pay/PRO');

    $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'yearly'])
        ->assertRedirect('https://uat.herepay.org/herepay/pay/PRO');

    $payment = $this->vendor->proPayments()->sole();

    expect($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->purpose)->toBe(PaymentPurpose::VendorPro)
        ->and($payment->detail('plan'))->toBe('yearly')
        ->and((float) $payment->amount)->toBe(490.0)
        ->and($this->vendor->fresh()->isPro())->toBeFalse();
});

it('offers no checkout while Herepay is not configured', function () {
    fakeHerepayKeys(enabled: false);

    $this->actingAs($this->owner)->get(route('vendor.pro.index'))
        ->assertOk()
        ->assertDontSee(__('pages.pro.pay_fpx'))
        ->assertSee(__('pages.pro.checkout_soon'));

    $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'monthly'])->assertNotFound();

    expect(Payment::count())->toBe(0);
});

it('refuses a plan that does not exist', function () {
    $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'lifetime'])
        ->assertSessionHasErrors('plan');
});

it('activates Pro when Herepay confirms the payment, once however often it calls', function () {
    Notification::fake();
    $this->freezeTime();

    $payment = Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id]);

    herepayCallback($payment)->assertOk();
    herepayCallback($payment)->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Paid)
        ->and($payment->fresh()->gateway_reference)->toBe('HP-PAY-1')
        ->and($payment->fresh()->detail('ends_at'))->toBe(now()->addMonth()->toIso8601String())
        ->and($this->vendor->fresh()->pro_until->toDateTimeString())->toBe(now()->addMonth()->toDateTimeString());

    Notification::assertSentToTimes($this->owner, ProActivated::class, 1);
});

it('adds a renewal onto the time the vendor has left', function () {
    $this->freezeTime();

    $this->vendor->update(['pro_until' => now()->addDays(10)]);
    $payment = Payment::factory()->pro(VendorPlan::Yearly)->create(['vendor_id' => $this->vendor->id]);

    herepayCallback($payment)->assertOk();

    expect($this->vendor->fresh()->pro_until->toDateTimeString())->toBe(now()->addDays(10)->addYear()->toDateTimeString());
});

it('ignores a callback it cannot verify', function () {
    $payment = Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id]);

    herepayCallback($payment, key: 'forged')->assertForbidden();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Pending)
        ->and($this->vendor->fresh()->isPro())->toBeFalse();
});

it('marks a failed payment without giving Pro', function () {
    $payment = Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id]);

    herepayCallback($payment, ['status' => 'Failed', 'status_code' => '30'])->assertOk();

    expect($payment->fresh()->status)->toBe(PaymentStatus::Failed)
        ->and($this->vendor->fresh()->isPro())->toBeFalse();
});

it('ends Pro on its own when the paid time runs out', function () {
    $this->vendor->update(['pro_until' => now()->addDay()]);

    expect($this->vendor->fresh()->isPro())->toBeTrue();

    $this->travel(2)->days();

    expect($this->vendor->fresh()->isPro())->toBeFalse()
        ->and(Vendor::pro()->count())->toBe(0);
});

it('lets an admin record a manual payment that activates Pro', function () {
    Notification::fake();
    $this->freezeTime();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.vendors.pro', $this->vendor), ['plan' => 'monthly', 'amount' => 0, 'note' => 'Promosi pelancaran'])
        ->assertRedirect();

    $payment = $this->vendor->proPayments()->sole();

    expect($payment->gateway)->toBe(Payment::GATEWAY_MANUAL)
        ->and($payment->isPaid())->toBeTrue()
        ->and((float) $payment->amount)->toBe(0.0)
        ->and($payment->recorded_by)->toBe($admin->id)
        ->and($payment->events()->pluck('type')->all())->toBe([PaymentEvent::MANUAL_RECORDED])
        ->and($this->vendor->fresh()->isPro())->toBeTrue();

    Notification::assertSentTo($this->owner, ProActivated::class);
});

it('does not let a vendor activate Pro through the admin route', function () {
    $this->actingAs($this->owner)
        ->post(route('admin.vendors.pro', $this->vendor), ['plan' => 'monthly'])
        ->assertForbidden();

    expect($this->vendor->fresh()->isPro())->toBeFalse();
});

it('saves the Pro prices from admin settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.pro'), ['enabled' => '1', 'monthly_price' => 59, 'yearly_price' => 590])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $settings = app(ProSettings::class);

    expect($settings->isEnabled())->toBeTrue()
        ->and($settings->price(VendorPlan::Monthly))->toBe(59.0);
});

it('reminds a vendor a week before Pro runs out, and not a day earlier', function () {
    Notification::fake();
    $this->freezeTime();

    $this->vendor->update(['pro_until' => now()->addDays(7)]);
    $later = Vendor::factory()->for(Category::first())->create(['pro_until' => now()->addDays(8)]);

    $this->artisan('neekah:pro-reminders')->assertSuccessful();

    Notification::assertSentTo($this->owner, ProExpiring::class, fn (ProExpiring $notification): bool => $notification->days === 7);
    Notification::assertNotSentTo($later->user, ProExpiring::class);
});
