<?php

use App\Enums\DepositType;
use App\Enums\InvoiceStatus;
use App\Enums\QuotationItemKind;
use App\Enums\QuotationStatus;
use App\Enums\VendorFeature;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\QuotationSent;
use Database\Seeders\CategorySeeder;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->create();
    $this->package = Package::factory()->for($this->vendor)->create(['name' => 'Pakej Emas', 'price' => 3000, 'features' => ['8 jam', '300 gambar']]);
});

/**
 * @return array<string, mixed>
 */
function quotationPayload(array $overrides = []): array
{
    return [
        'client_name' => 'Aina Hakim',
        'client_phone' => '0123456789',
        'client_email' => 'aina@example.com',
        'event_date' => now()->addMonths(3)->toDateString(),
        'event_location' => 'Dewan Seri, Shah Alam',
        'valid_until' => now()->addDays(14)->toDateString(),
        'discount_type' => 'percent',
        'discount_value' => 10,
        'deposit_type' => 'percent',
        'deposit_value' => 30,
        'terms' => 'Deposit tidak dikembalikan.',
        'items' => [
            ['package_id' => test()->package->id, 'quantity' => 1, 'unit_price' => 3000],
            ['name' => 'Jam tambahan', 'quantity' => 2, 'unit_price' => 250],
        ],
        ...$overrides,
    ];
}

it('sends a Basic vendor to the Pro page and refuses their writes', function () {
    $basic = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($basic->user)
        ->get(route('vendor.quotations.index'))
        ->assertRedirect(route('vendor.pro.index'))
        ->assertSessionHas('status', __('flash.vendor.feature_needs_pro', ['feature' => VendorFeature::Quotations->label()]));

    $this->actingAs($basic->user)->post(route('vendor.quotations.store'), quotationPayload())->assertForbidden();

    expect(Quotation::count())->toBe(0);
});

it('saves a quotation from a package and an add-on, working out every sum itself', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.quotations.store'), quotationPayload([
            'save_terms_as_default' => '1',
            // Sums posted by the form are ignored.
            'total' => 1,
            'deposit_amount' => 1,
        ]))
        ->assertRedirect();

    $quotation = Quotation::sole();

    // 3000 + 2 × 250 = 3500, less 10% = 3150, deposit 30% = 945.
    expect($quotation->number)->toBe('QT-1001')
        ->and($quotation->status)->toBe(QuotationStatus::Draft)
        ->and((float) $quotation->subtotal)->toBe(3500.0)
        ->and((float) $quotation->discount_amount)->toBe(350.0)
        ->and((float) $quotation->total)->toBe(3150.0)
        ->and((float) $quotation->deposit_amount)->toBe(945.0)
        ->and($quotation->balanceAmount())->toBe(2205.0)
        ->and($quotation->items->pluck('kind')->all())->toBe([QuotationItemKind::Package, QuotationItemKind::Addon])
        ->and($quotation->items->first()->name)->toBe('Pakej Emas')
        ->and($quotation->items->first()->features)->toBe(['8 jam', '300 gambar'])
        ->and($this->vendor->bookingSettings()->value('quotation_terms'))->toBe('Deposit tidak dikembalikan.');

    // The snapshot holds when the package changes afterwards.
    $this->package->update(['name' => 'Pakej Baharu', 'price' => 9000]);
    expect($quotation->fresh()->items->first()->name)->toBe('Pakej Emas');
});

it('caps a fixed discount at the subtotal and a fixed deposit at the total', function () {
    $quotation = Quotation::factory()->for($this->vendor)->create([
        'discount_type' => DepositType::Fixed,
        'discount_value' => 5000,
        'deposit_type' => DepositType::Fixed,
        'deposit_value' => 500,
    ]);

    expect((float) $quotation->fresh()->total)->toBe(0.0)
        ->and((float) $quotation->fresh()->deposit_amount)->toBe(0.0);

    $quotation->update(['discount_value' => 1000, 'deposit_value' => 99999]);
    $quotation->recalculate();

    expect((float) $quotation->total)->toBe(2000.0)
        ->and((float) $quotation->deposit_amount)->toBe(2000.0);
});

it('numbers each vendor\'s quotations on their own', function () {
    $other = Vendor::factory()->pro()->for(Category::first())->create();

    $this->actingAs($this->vendor->user)->post(route('vendor.quotations.store'), quotationPayload());
    $this->actingAs($this->vendor->user)->post(route('vendor.quotations.store'), quotationPayload());
    $this->actingAs($other->user)->post(route('vendor.quotations.store'), quotationPayload(['items' => [['name' => 'Pelamin', 'quantity' => 1, 'unit_price' => 1000]]]));

    expect($this->vendor->quotations()->orderBy('id')->pluck('number')->all())->toBe(['QT-1001', 'QT-1002'])
        ->and($other->quotations()->value('number'))->toBe('QT-1001');
});

it('refuses a quotation without its required fields', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.quotations.store'), [])
        ->assertSessionHasErrors(['client_name', 'valid_until', 'discount_type', 'deposit_type', 'items']);
});

it('refuses another vendor\'s package and a discount over 100%', function () {
    $foreign = Package::factory()->for(Vendor::factory()->for(Category::first()))->create();

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.quotations.store'), quotationPayload([
            'discount_value' => 150,
            'items' => [['package_id' => $foreign->id, 'quantity' => 1, 'unit_price' => 100]],
        ]))
        ->assertSessionHasErrors(['discount_value', 'items.0.package_id']);

    expect(Quotation::count())->toBe(0);
});

it('keeps a vendor out of another vendor\'s quotations', function () {
    $theirs = Quotation::factory()->for(Vendor::factory()->pro()->for(Category::first()))->create();

    $this->actingAs($this->vendor->user)->get(route('vendor.quotations.show', $theirs))->assertForbidden();
    $this->actingAs($this->vendor->user)->put(route('vendor.quotations.update', $theirs), quotationPayload())->assertForbidden();
    $this->actingAs($this->vendor->user)->post(route('vendor.quotations.send', $theirs))->assertForbidden();

    $rows = $this->actingAs($this->vendor->user)->getJson(route('vendor.quotations.data'))->assertOk()->json('data');
    expect($rows)->toBe([]);
});

it('lists the vendor\'s quotations', function () {
    Quotation::factory()->for($this->vendor)->sent()->create(['client_name' => 'Aina Hakim', 'number' => 'QT-1001']);

    $this->actingAs($this->vendor->user)->get(route('vendor.quotations.index'))->assertOk()->assertSee('data-vue="data-table"', false);

    $rows = $this->actingAs($this->vendor->user)
        ->getJson(route('vendor.quotations.data', ['sort' => 'total', 'direction' => 'asc', 'search' => 'Aina']))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->json('data');

    expect($rows[0]['client'])->toBe('Aina Hakim')
        ->and($rows[0]['total'])->toBe('RM3,000.00');
});

it('opens a sent quotation to the client and emails them', function () {
    Notification::fake();
    $quotation = Quotation::factory()->for($this->vendor)->create(['client_email' => 'aina@example.com']);

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.quotations.send', $quotation))
        ->assertSessionHas('status', __('flash.vendor.quotation_emailed', ['number' => $quotation->number]));

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Sent)
        ->and($quotation->fresh()->sent_at)->not->toBeNull();

    Notification::assertSentOnDemand(QuotationSent::class, fn (QuotationSent $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'aina@example.com');
});

it('locks an answered quotation and revises it as a new draft', function () {
    $accepted = Quotation::factory()->for($this->vendor)->accepted()->create();

    $this->actingAs($this->vendor->user)->get(route('vendor.quotations.edit', $accepted))->assertForbidden();
    $this->actingAs($this->vendor->user)->put(route('vendor.quotations.update', $accepted), quotationPayload())->assertForbidden();
    $this->actingAs($this->vendor->user)->delete(route('vendor.quotations.destroy', $accepted))->assertForbidden();

    $this->actingAs($this->vendor->user)->post(route('vendor.quotations.duplicate', $accepted))->assertRedirect();

    $copy = Quotation::latest('id')->first();
    expect($copy->id)->not->toBe($accepted->id)
        ->and($copy->status)->toBe(QuotationStatus::Draft)
        ->and($copy->token)->not->toBe($accepted->token)
        ->and($copy->accepted_at)->toBeNull()
        ->and((float) $copy->total)->toBe((float) $accepted->total)
        ->and($accepted->fresh()->status)->toBe(QuotationStatus::Accepted);
});

it('deletes a draft', function () {
    $draft = Quotation::factory()->for($this->vendor)->create();

    $this->actingAs($this->vendor->user)->delete(route('vendor.quotations.destroy', $draft))->assertRedirect(route('vendor.quotations.index'));

    expect(Quotation::count())->toBe(0);
});

it('issues an invoice only once the client has accepted, under its own number', function () {
    $sent = Quotation::factory()->for($this->vendor)->sent()->create();
    $this->actingAs($this->vendor->user)->post(route('vendor.quotations.invoice', $sent))->assertForbidden();

    $accepted = Quotation::factory()->for($this->vendor)->accepted()->create();
    $this->actingAs($this->vendor->user)->post(route('vendor.quotations.invoice', $accepted))->assertRedirect();
    $this->actingAs($this->vendor->user)->post(route('vendor.quotations.invoice', $accepted))->assertRedirect();

    expect($accepted->fresh()->invoice_number)->toBe('INV-1001')
        ->and($accepted->fresh()->invoice_status)->toBe(InvoiceStatus::Unpaid)
        ->and($sent->fresh()->invoice_number)->toBeNull();

    $this->actingAs($this->vendor->user)
        ->put(route('vendor.quotations.invoice-status', $accepted), ['invoice_status' => 'deposit_paid'])
        ->assertRedirect();

    expect($accepted->fresh()->invoice_status)->toBe(InvoiceStatus::DepositPaid);
});

it('records a booking at the accepted quotation\'s total and deposit', function () {
    $customer = User::factory()->create(['email' => 'aina@example.com']);
    $quotation = Quotation::factory()->for($this->vendor)->accepted()->create(['client_email' => 'aina@example.com']);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.bookings.create', ['quotation' => $quotation->token]))
        ->assertOk()
        ->assertViewHas('props', fn (array $props) => $props['quotation']['number'] === $quotation->number);

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.store'), [
            'customer_email' => 'aina@example.com',
            'package_id' => $this->package->id,
            'event_date' => now()->addMonths(2)->toDateString(),
            'quotation_id' => $quotation->id,
        ])
        ->assertRedirect();

    $booking = Booking::sole();

    // The factory's quotation is RM3,000 with a 30% deposit; the package is
    // also RM3,000, so the deposit is what proves the quotation was used.
    expect($booking->user_id)->toBe($customer->id)
        ->and((float) $booking->total_amount)->toBe(3000.0)
        ->and((float) $booking->deposit_amount)->toBe(900.0)
        ->and($quotation->fresh()->booking_id)->toBe($booking->id);

    // Once linked it cannot start a second booking.
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.bookings.store'), [
            'customer_email' => 'aina@example.com',
            'package_id' => $this->package->id,
            'event_date' => now()->addMonths(4)->toDateString(),
            'quotation_id' => $quotation->id,
        ])
        ->assertSessionHasErrors('quotation_id');
});

it('starts a quotation from an enquiry with the couple and their package filled in', function () {
    $couple = User::factory()->create(['name' => 'Siti Nur', 'email' => 'siti@example.com']);
    $enquiry = Enquiry::factory()->for($couple)->for($this->vendor)->create(['package_id' => $this->package->id]);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.enquiries.show', $enquiry))
        ->assertViewHas('props', fn (array $props) => $props['quotationUrl'] === route('vendor.quotations.create', ['enquiry' => $enquiry->id]));

    $props = $this->actingAs($this->vendor->user)
        ->get(route('vendor.quotations.create', ['enquiry' => $enquiry->id]))
        ->assertOk()
        ->viewData('props');

    expect($props['quotation']['client_name'])->toBe('Siti Nur')
        ->and($props['quotation']['client_email'])->toBe('siti@example.com')
        ->and($props['quotation']['enquiry_id'])->toBe($enquiry->id)
        ->and($props['quotation']['items'][0]['package_id'])->toBe($this->package->id);
});

it('addresses a quotation by its token in the vendor area too, never by its id', function () {
    $quotation = Quotation::factory()->for($this->vendor)->create();

    expect(route('vendor.quotations.show', $quotation))->toEndWith('/'.$quotation->token);

    $this->actingAs($this->vendor->user)->get('/vendor/sebut-harga/'.$quotation->id)->assertNotFound();
    $this->actingAs($this->vendor->user)
        ->get(route('vendor.quotations.show', $quotation))
        ->assertOk()
        ->assertSee('data-doc', false)
        ->assertSee($quotation->number);
});

it('saves and sends a new quotation in one step', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.quotations.store'), quotationPayload(['client_email' => null, 'send' => '1']))
        ->assertRedirect()
        ->assertSessionHas('status', __('flash.vendor.quotation_sent', ['number' => 'QT-1001']));

    expect(Quotation::sole()->status)->toBe(QuotationStatus::Sent);
});
