<?php

use App\Enums\ContractStatus;
use App\Enums\VendorFeature;
use App\Models\Category;
use App\Models\Contract;
use App\Models\Quotation;
use App\Models\Vendor;
use App\Notifications\ContractSent;
use Database\Seeders\CategorySeeder;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->create();
});

/**
 * @return array<string, mixed>
 */
function contractPayload(array $overrides = []): array
{
    return [
        'client_name' => 'Aina Hakim',
        'client_email' => 'aina@example.com',
        'event_date' => now()->addMonths(3)->toDateString(),
        'sections' => [
            ['key' => 'scope', 'title' => 'Skop kerja', 'body' => 'Liputan 8 jam.'],
            ['key' => 'copyright', 'title' => 'Hak cipta', 'body' => 'Milik vendor.'],
            ['key' => '', 'title' => 'Parkir', 'body' => 'Disediakan oleh klien.'],
            // Left empty: dropped, not refused.
            ['key' => 'terms', 'title' => '', 'body' => ''],
        ],
        ...$overrides,
    ];
}

it('sends a Basic vendor to the Pro page and refuses their writes', function () {
    $basic = Vendor::factory()->for(Category::first())->create();

    $this->actingAs($basic->user)
        ->get(route('vendor.contracts.index'))
        ->assertRedirect(route('vendor.pro.index'))
        ->assertSessionHas('status', __('flash.vendor.feature_needs_pro', ['feature' => VendorFeature::Contracts->label()]));

    $this->actingAs($basic->user)->post(route('vendor.contracts.store'), contractPayload())->assertForbidden();
});

it('starts a contract with the five standard sections, filled with text to edit', function () {
    $props = $this->actingAs($this->vendor->user)
        ->get(route('vendor.contracts.create'))
        ->assertOk()
        ->viewData('props');

    expect(array_column($props['contract']['sections'], 'key'))->toBe(Contract::SECTION_KEYS)
        ->and($props['contract']['sections'][0]['body'])->toBe(__('pages.contracts.starter.scope'))
        // Nothing saved yet, so the first contract becomes the vendor's own default.
        ->and($props['saveAsDefault'])->toBeTrue();
});

it('asks which quotation a new contract is for, and lets the vendor start without one', function () {
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create(['client_name' => 'Siti Nur']);
    Quotation::factory()->for($this->vendor)->create(['status' => 'declined', 'client_name' => 'Ditolak Sahaja']);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.contracts.create'))
        ->assertOk()
        ->assertViewIs('vendor.contracts.pick')
        ->assertSee('Siti Nur')
        ->assertDontSee('Ditolak Sahaja')
        ->assertSee(route('vendor.contracts.create', ['quotation' => $quotation->token]), false);

    $this->actingAs($this->vendor->user)
        ->get(route('vendor.contracts.create', ['kosong' => 1]))
        ->assertOk()
        ->assertViewIs('vendor.contracts.form')
        ->assertViewHas('props', fn (array $props) => $props['quotation'] === null);
});

it('saves and sends in one step', function () {
    Notification::fake();

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.contracts.store'), contractPayload(['send' => '1']))
        ->assertRedirect()
        ->assertSessionHas('status', fn (string $status) => str_contains($status, 'KT-1001'));

    expect(Contract::sole()->status)->toBe(ContractStatus::Sent);
    Notification::assertSentOnDemand(ContractSent::class);
});

it('saves a draft contract, numbered per vendor, and keeps the text as the default when asked', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.contracts.store'), contractPayload(['save_as_default' => '1']))
        ->assertRedirect();

    $contract = Contract::sole();

    expect($contract->number)->toBe('KT-1001')
        ->and($contract->status)->toBe(ContractStatus::Draft)
        ->and(array_column($contract->sections, 'title'))->toBe(['Skop kerja', 'Hak cipta', 'Parkir'])
        ->and($contract->sections[2]['key'])->toStartWith('custom-')
        ->and($this->vendor->bookingSettings()->first()->contract_defaults)->toBe(['scope' => 'Liputan 8 jam.', 'copyright' => 'Milik vendor.']);

    $this->actingAs($this->vendor->user)->get(route('vendor.contracts.show', $contract))->assertOk()->assertSee('Liputan 8 jam.');
});

it('refuses a contract with no client or no sections', function () {
    $this->actingAs($this->vendor->user)
        ->post(route('vendor.contracts.store'), ['sections' => [['title' => '', 'body' => '']]])
        ->assertSessionHasErrors(['client_name', 'sections']);
});

it('attaches only the vendor\'s own quotation, and starts from one with its client', function () {
    $ours = Quotation::factory()->for($this->vendor)->sent()->create(['client_name' => 'Siti Nur']);
    $theirs = Quotation::factory()->for(Vendor::factory()->for(Category::first()))->create();

    $this->actingAs($this->vendor->user)
        ->post(route('vendor.contracts.store'), contractPayload(['quotation_id' => $theirs->id]))
        ->assertSessionHasErrors('quotation_id');

    $props = $this->actingAs($this->vendor->user)
        ->get(route('vendor.contracts.create', ['quotation' => $ours->token]))
        ->viewData('props');

    expect($props['contract']['client_name'])->toBe('Siti Nur')
        ->and($props['quotation']['id'])->toBe($ours->id)
        ->and($props['quotation']['total'])->toBe('RM3,000.00');
});

it('keeps a vendor out of another vendor\'s contracts, and addresses them by token', function () {
    $theirs = Contract::factory()->for(Vendor::factory()->pro()->for(Category::first()))->create();

    $this->actingAs($this->vendor->user)->get(route('vendor.contracts.show', $theirs))->assertForbidden();
    $this->actingAs($this->vendor->user)->post(route('vendor.contracts.send', $theirs))->assertForbidden();
    $this->actingAs($this->vendor->user)->get('/vendor/kontrak/'.$theirs->id)->assertNotFound();
});

it('locks the text once sent, and emails the client the link', function () {
    Notification::fake();
    $contract = Contract::factory()->for($this->vendor)->create(['client_email' => 'aina@example.com']);

    $this->actingAs($this->vendor->user)->post(route('vendor.contracts.send', $contract))->assertRedirect();

    $contract->refresh();
    expect($contract->status)->toBe(ContractStatus::Sent)
        ->and($contract->vendor_signatory)->toBe($this->vendor->user->name);

    Notification::assertSentOnDemand(ContractSent::class, fn (ContractSent $notification, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'aina@example.com');

    $this->actingAs($this->vendor->user)->get(route('vendor.contracts.edit', $contract))->assertForbidden();
    $this->actingAs($this->vendor->user)->put(route('vendor.contracts.update', $contract), contractPayload())->assertForbidden();
});

it('withdraws an unsigned contract but never a signed one', function () {
    $sent = Contract::factory()->for($this->vendor)->sent()->create();
    $signed = Contract::factory()->for($this->vendor)->signed()->create();

    $this->actingAs($this->vendor->user)->post(route('vendor.contracts.void', $sent), ['reason' => 'Tarikh berubah'])->assertRedirect();
    $this->actingAs($this->vendor->user)->post(route('vendor.contracts.void', $signed))->assertForbidden();

    expect($sent->fresh()->status)->toBe(ContractStatus::Void)
        ->and($sent->fresh()->void_reason)->toBe('Tarikh berubah')
        ->and($signed->fresh()->status)->toBe(ContractStatus::Signed);
});

it('copies a sent contract into a new draft to change it', function () {
    $sent = Contract::factory()->for($this->vendor)->sent()->create();

    $this->actingAs($this->vendor->user)->post(route('vendor.contracts.duplicate', $sent))->assertRedirect();

    $copy = Contract::latest('id')->first();
    expect($copy->status)->toBe(ContractStatus::Draft)
        ->and($copy->token)->not->toBe($sent->token)
        ->and($copy->sections)->toBe($sent->sections)
        ->and($copy->sent_at)->toBeNull();
});
