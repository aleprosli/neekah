<?php

use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Enums\ViolationAction;
use App\Enums\ViolationStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorViolation;
use App\Notifications\VendorViolationRecorded;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->vendor = Vendor::factory()->for(Category::first())->tier(VendorTier::Top)->create();
});

it('lets a customer report a vendor', function () {
    $booking = Booking::factory()->completed()->for($this->customer)->for($this->vendor)->create();

    $this->actingAs($this->customer)->get(route('vendors.report.create', $this->vendor))->assertOk()->assertSee('Laporkan');

    $this->actingAs($this->customer)
        ->post(route('vendors.report.store', $this->vendor), [
            'type' => 'payment_bypass',
            'booking_id' => $booking->id,
            'description' => 'Vendor minta saya bayar terus ke akaun bank dan batalkan booking Neekah.',
        ])
        ->assertRedirect(route('vendors.show', $this->vendor));

    $violation = VendorViolation::sole();

    expect($violation->vendor_id)->toBe($this->vendor->id)
        ->and($violation->reported_by)->toBe($this->customer->id)
        ->and($violation->booking_id)->toBe($booking->id)
        ->and($violation->status)->toBe(ViolationStatus::Open);
});

it('rejects a short report, a guest and a vendor reporting', function () {
    $this->post(route('vendors.report.store', $this->vendor), ['type' => 'other', 'description' => str_repeat('a', 30)])
        ->assertRedirect(route('login'));

    $this->actingAs($this->customer)
        ->post(route('vendors.report.store', $this->vendor), ['type' => 'other', 'description' => 'pendek'])
        ->assertSessionHasErrors('description');

    $this->actingAs($this->vendor->user)
        ->post(route('vendors.report.store', $this->vendor), ['type' => 'other', 'description' => str_repeat('a', 30)])
        ->assertForbidden();

    expect(VendorViolation::count())->toBe(0);
});

it('applies the escalation ladder across repeated upheld violations', function () {
    Notification::fake();

    // First offence: warning only.
    $first = VendorViolation::factory()->for($this->vendor)->create();
    $this->actingAs($this->admin)->put(route('admin.violations.update', $first), ['decision' => 'uphold', 'admin_note' => 'Bukti WhatsApp disemak.'])->assertRedirect();

    $this->vendor->refresh();
    expect($first->fresh()->action)->toBe(ViolationAction::Warning)
        ->and($first->fresh()->offence_number)->toBe(1)
        ->and($this->vendor->penalty_points)->toBe(0)
        ->and($this->vendor->tier)->toBe(VendorTier::Top)
        ->and($this->vendor->status)->toBe(VendorStatus::Approved);

    // Second offence: point deduction and a ranking drop.
    $second = VendorViolation::factory()->for($this->vendor)->create();
    $this->actingAs($this->admin)->put(route('admin.violations.update', $second), ['decision' => 'uphold'])->assertRedirect();

    $this->vendor->refresh();
    expect($second->fresh()->action)->toBe(ViolationAction::PointDeduction)
        ->and($this->vendor->penalty_points)->toBe(100)
        ->and($this->vendor->tier)->toBe(VendorTier::Trusted)
        ->and($this->vendor->status)->toBe(VendorStatus::Approved);

    // Third offence: temporary suspension.
    $third = VendorViolation::factory()->for($this->vendor)->create();
    $this->actingAs($this->admin)->put(route('admin.violations.update', $third), ['decision' => 'uphold'])->assertRedirect();

    $this->vendor->refresh();
    expect($third->fresh()->action)->toBe(ViolationAction::Suspension)
        ->and($this->vendor->status)->toBe(VendorStatus::Suspended)
        ->and($this->vendor->penalty_points)->toBe(300);

    $this->get(route('vendors.show', $this->vendor))->assertNotFound();

    // Fourth offence: removal.
    $fourth = VendorViolation::factory()->for($this->vendor)->create();
    $this->actingAs($this->admin)->put(route('admin.violations.update', $fourth), ['decision' => 'uphold'])->assertRedirect();

    $this->vendor->refresh();
    expect($fourth->fresh()->action)->toBe(ViolationAction::Removal)
        ->and($this->vendor->status)->toBe(VendorStatus::Rejected)
        ->and($this->vendor->tier)->toBe(VendorTier::New)
        ->and($this->vendor->violations_count)->toBe(4);

    Notification::assertSentToTimes($this->vendor->user, VendorViolationRecorded::class, 4);
});

it('dismisses a report without penalising the vendor', function () {
    Notification::fake();
    $violation = VendorViolation::factory()->for($this->vendor)->create();

    $this->actingAs($this->admin)
        ->put(route('admin.violations.update', $violation), ['decision' => 'dismiss', 'admin_note' => 'Tiada bukti.'])
        ->assertRedirect();

    $this->vendor->refresh();

    expect($violation->fresh()->status)->toBe(ViolationStatus::Dismissed)
        ->and($violation->fresh()->action)->toBeNull()
        ->and($this->vendor->penalty_points)->toBe(0)
        ->and($this->vendor->tier)->toBe(VendorTier::Top);

    Notification::assertNothingSent();
});

it('refuses to resolve the same report twice', function () {
    $violation = VendorViolation::factory()->upheld()->for($this->vendor)->create();

    $this->actingAs($this->admin)
        ->put(route('admin.violations.update', $violation), ['decision' => 'uphold'])
        ->assertSessionHasErrors('decision');
});

it('lowers the vendor score through penalty points', function () {
    $before = $this->vendor->calculateScore();

    VendorViolation::factory()->for($this->vendor)->count(2)->create()->each(
        fn ($violation) => $this->actingAs($this->admin)->put(route('admin.violations.update', $violation), ['decision' => 'uphold'])
    );

    expect((float) $this->vendor->fresh()->score)->toBeLessThan($before);
});

it('keeps customers and vendors out of the violation queue', function () {
    $violation = VendorViolation::factory()->for($this->vendor)->create();

    $this->actingAs($this->customer)->get(route('admin.violations.index'))->assertForbidden();
    $this->actingAs($this->vendor->user)->put(route('admin.violations.update', $violation), ['decision' => 'dismiss'])->assertForbidden();

    $this->actingAs($this->admin)->get(route('admin.violations.index'))->assertOk()->assertSee($this->vendor->name);
    $this->actingAs($this->admin)->get(route('admin.violations.show', $violation))->assertOk()->assertSee('Tolak laporan');
});
