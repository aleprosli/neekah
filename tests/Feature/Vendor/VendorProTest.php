<?php

use App\Enums\SubscriptionStatus;
use App\Enums\VendorPlan;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorSubscription;
use App\Notifications\ProActivated;
use App\Notifications\ProExpiring;
use App\Support\Herepay\PaymentLinkGateway;
use App\Support\ProSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

/**
 * A stand-in for Herepay: a fixed payment link out, and a callback it trusts
 * only when it carries the test signature.
 */
function fakeProGateway(bool $configured = true): PaymentLinkGateway
{
    $gateway = new class($configured) implements PaymentLinkGateway
    {
        public function __construct(private bool $configured) {}

        public function isConfigured(): bool
        {
            return $this->configured;
        }

        public function createPaymentLink(VendorSubscription $subscription, User $payer): string
        {
            return 'https://pay.herepay.test/'.$subscription->reference;
        }

        public function parseCallback(Request $request): ?array
        {
            if ($request->input('signature') !== 'valid') {
                return null;
            }

            return [
                'reference' => (string) $request->input('reference'),
                'gateway_reference' => $request->input('transaction_id'),
                'paid' => $request->input('status') === 'paid',
            ];
        }
    };

    app()->instance(PaymentLinkGateway::class, $gateway);

    return $gateway;
}

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->owner = User::factory()->vendor()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create();
    app(ProSettings::class)->save(['enabled' => true, 'monthly_price' => 49, 'yearly_price' => 490]);
});

it('sends the vendor to a payment link for the plan they picked, at todays price', function () {
    fakeProGateway();

    $response = $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'yearly']);

    $subscription = $this->vendor->subscriptions()->sole();

    $response->assertRedirect('https://pay.herepay.test/'.$subscription->reference);
    expect($subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and((float) $subscription->amount)->toBe(490.0)
        ->and($this->vendor->fresh()->isPro())->toBeFalse();
});

it('offers no checkout while Herepay is not configured', function () {
    fakeProGateway(configured: false);

    $this->actingAs($this->owner)->get(route('vendor.pro.index'))
        ->assertOk()
        ->assertDontSee(__('pages.pro.pay_fpx'))
        ->assertSee(__('pages.pro.checkout_soon'));

    $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'monthly'])->assertNotFound();

    expect(VendorSubscription::count())->toBe(0);
});

it('refuses a plan that does not exist', function () {
    fakeProGateway();

    $this->actingAs($this->owner)->post(route('vendor.pro.checkout'), ['plan' => 'lifetime'])
        ->assertSessionHasErrors('plan');
});

it('activates Pro when Herepay confirms the payment, once however often it calls', function () {
    fakeProGateway();
    Notification::fake();
    $this->freezeTime();

    $subscription = VendorSubscription::factory()->for($this->vendor)->create();
    $callback = ['signature' => 'valid', 'reference' => $subscription->reference, 'status' => 'paid', 'transaction_id' => 'HP-1'];

    $this->post(route('webhooks.herepay'), $callback)->assertOk();
    $this->post(route('webhooks.herepay'), $callback)->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Paid)
        ->and($subscription->fresh()->gateway_reference)->toBe('HP-1')
        ->and($this->vendor->fresh()->pro_until->toDateTimeString())->toBe(now()->addMonth()->toDateTimeString());

    Notification::assertSentToTimes($this->owner, ProActivated::class, 1);
});

it('adds a renewal onto the time the vendor has left', function () {
    fakeProGateway();
    $this->freezeTime();

    $this->vendor->update(['pro_until' => now()->addDays(10)]);
    $subscription = VendorSubscription::factory()->yearly()->for($this->vendor)->create();

    $this->post(route('webhooks.herepay'), ['signature' => 'valid', 'reference' => $subscription->reference, 'status' => 'paid']);

    expect($this->vendor->fresh()->pro_until->toDateTimeString())->toBe(now()->addDays(10)->addYear()->toDateTimeString());
});

it('ignores a callback it cannot verify', function () {
    fakeProGateway();

    $subscription = VendorSubscription::factory()->for($this->vendor)->create();

    $this->post(route('webhooks.herepay'), ['signature' => 'forged', 'reference' => $subscription->reference, 'status' => 'paid'])
        ->assertForbidden();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Pending)
        ->and($this->vendor->fresh()->isPro())->toBeFalse();
});

it('marks a failed payment without giving Pro', function () {
    fakeProGateway();

    $subscription = VendorSubscription::factory()->for($this->vendor)->create();

    $this->post(route('webhooks.herepay'), ['signature' => 'valid', 'reference' => $subscription->reference, 'status' => 'failed'])
        ->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Failed)
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

    $subscription = $this->vendor->subscriptions()->sole();

    expect($subscription->gateway)->toBe(VendorSubscription::GATEWAY_MANUAL)
        ->and((float) $subscription->amount)->toBe(0.0)
        ->and($subscription->added_by)->toBe($admin->id)
        ->and($this->vendor->fresh()->isPro())->toBeTrue();

    Notification::assertSentTo($this->owner, ProActivated::class);
});

it('does not let a vendor activate Pro through the admin route', function () {
    $this->actingAs($this->owner)
        ->post(route('admin.vendors.pro', $this->vendor), ['plan' => 'monthly'])
        ->assertForbidden();

    expect($this->vendor->fresh()->isPro())->toBeFalse();
});

it('saves the Pro prices and slots from admin settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.pro'), ['enabled' => '1', 'monthly_price' => 59, 'yearly_price' => 590, 'sponsored_slots' => 4])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $settings = app(ProSettings::class);

    expect($settings->isEnabled())->toBeTrue()
        ->and($settings->price(VendorPlan::Monthly))->toBe(59.0)
        ->and($settings->sponsoredSlots())->toBe(4);
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
