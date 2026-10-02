<?php

use App\Enums\QuotationStatus;
use App\Models\Category;
use App\Models\Quotation;
use App\Models\Vendor;
use App\Notifications\QuotationAccepted;
use App\Notifications\QuotationDeclined;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);
    $this->vendor = Vendor::factory()->pro()->for(Category::first())->create(['name' => 'Studio Cahaya']);
});

it('opens a sent quotation by its token without an account, and notes it was viewed', function () {
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create(['number' => 'QT-1023']);

    $this->get(route('quotations.public.show', $quotation->token))
        ->assertOk()
        ->assertSee('QT-1023')
        ->assertSee('Studio Cahaya')
        ->assertSee('RM3,000.00')
        ->assertSee('noindex', false)
        ->assertSee(route('quotations.public.accept', $quotation->token), false);

    expect($quotation->fresh()->viewed_at)->not->toBeNull();
});

it('answers 404 for a token nobody was given, and never by the number', function () {
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create(['number' => 'QT-1023']);

    $this->get('/q/QT-1023')->assertNotFound();
    $this->get('/q/'.$quotation->id)->assertNotFound();
});

it('shows a draft only to its own vendor, and their look is not a view', function () {
    $draft = Quotation::factory()->for($this->vendor)->create();

    $this->get(route('quotations.public.show', $draft->token))->assertNotFound();

    $this->actingAs($this->vendor->user)
        ->get(route('quotations.public.show', $draft->token))
        ->assertOk()
        ->assertSee(__('pages.quotation_doc.draft_preview'));

    expect($draft->fresh()->viewed_at)->toBeNull();
});

it('records who accepted and tells the vendor', function () {
    Notification::fake();
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create();

    $this->post(route('quotations.public.accept', $quotation->token), ['name' => 'Aina Hakim', 'agree' => '1'])
        ->assertRedirect($quotation->publicUrl())
        ->assertSessionHas('status', __('flash.quotation.accepted'));

    $quotation->refresh();
    expect($quotation->status)->toBe(QuotationStatus::Accepted)
        ->and($quotation->accepted_name)->toBe('Aina Hakim')
        ->and($quotation->accepted_ip)->toBe('127.0.0.1')
        ->and($quotation->accepted_at)->not->toBeNull();

    Notification::assertSentTo($this->vendor->user, QuotationAccepted::class);
});

it('needs a name and the tick to accept', function () {
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create();

    $this->post(route('quotations.public.accept', $quotation->token), [])
        ->assertSessionHasErrors(['name', 'agree']);

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Sent);
});

it('refuses an answer once the quotation is expired or already answered', function (string $state) {
    Notification::fake();
    $quotation = Quotation::factory()->for($this->vendor)->{$state}()->create();
    $before = $quotation->status;

    $this->post(route('quotations.public.accept', $quotation->token), ['name' => 'Aina Hakim', 'agree' => '1'])
        ->assertSessionHasErrors(['name' => __('validation.custom.quotation_closed')]);
    $this->post(route('quotations.public.decline', $quotation->token), [])
        ->assertSessionHasErrors('reason');

    expect($quotation->fresh()->status)->toBe($before);
    Notification::assertNothingSent();
})->with(['expired', 'accepted']);

it('takes a decline with its reason and tells the vendor', function () {
    Notification::fake();
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create();

    $this->post(route('quotations.public.decline', $quotation->token), ['reason' => 'Bajet tidak cukup'])->assertRedirect();

    expect($quotation->fresh()->status)->toBe(QuotationStatus::Declined)
        ->and($quotation->fresh()->decline_reason)->toBe('Bajet tidak cukup');

    Notification::assertSentTo($this->vendor->user, QuotationDeclined::class);
});

it('shows the invoice at the same link once issued', function () {
    $quotation = Quotation::factory()->for($this->vendor)->accepted()->create(['invoice_number' => 'INV-1001', 'invoiced_at' => now(), 'invoice_status' => 'unpaid']);

    $this->get(route('quotations.public.show', $quotation->token))
        ->assertOk()
        ->assertSee(__('pages.quotation_doc.invoice'))
        ->assertSee('INV-1001')
        ->assertDontSee(route('quotations.public.accept', $quotation->token), false);
});

it('keeps the link working after the vendor\'s Pro has ended', function () {
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create();
    $this->vendor->update(['pro_until' => now()->subDay()]);

    $this->get(route('quotations.public.show', $quotation->token))->assertOk();
});

it('escapes what the vendor and the client typed', function () {
    $quotation = Quotation::factory()->for($this->vendor)->sent()->create([
        'client_name' => '<script>alert(1)</script>',
        'terms' => '<img src=x onerror=alert(2)>',
    ]);

    $this->get(route('quotations.public.show', $quotation->token))
        ->assertOk()
        ->assertDontSee('<script>alert(1)</script>', false)
        ->assertDontSee('<img src=x onerror=alert(2)>', false);
});
