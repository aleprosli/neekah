<?php

use App\Actions\SettlePayment;
use App\Actions\VerifyManualPayment;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Models\Booking;
use App\Models\Category;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Notifications\PaymentReceipt;
use App\Notifications\ProActivated;
use App\Support\InvoiceSettings;
use Database\Seeders\CategorySeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    $this->seed(CategorySeeder::class);

    $this->owner = User::factory()->vendor()->create(['email' => 'studio@example.com']);
    $this->vendor = Vendor::factory()->for(Category::first())->for($this->owner)->create(['name' => 'Studio Seri']);
    $this->admin = User::factory()->admin()->create();
    $this->proPayment = fn (array $attributes = []): Payment => Payment::factory()->pro()->create(['vendor_id' => $this->vendor->id, 'amount' => 49, ...$attributes]);
});

it('numbers the receipt and emails it once when a payment is settled, however often that happens', function () {
    Notification::fake();
    $this->travelTo('2026-09-27 10:00');
    $payment = ($this->proPayment)();

    $this->actingAs($this->admin)->post(route('admin.payments.paid', $payment), ['note' => 'Dilihat di papan pemuka Herepay'])->assertRedirect();
    app(SettlePayment::class)->markPaid($payment);

    $payment->refresh();
    expect($payment->receipt_number)->toBe('RS-2026-000001')
        ->and($payment->receipt_sent_at)->not->toBeNull();
    Notification::assertSentToTimes($this->owner, PaymentReceipt::class, 1);
    Notification::assertSentTo($this->owner, ProActivated::class, fn (ProActivated $notification): bool => $notification->via($this->owner) === ['database']);
});

it('keeps receipt numbers in sequence and starts again each year', function () {
    Notification::fake();
    $settle = app(SettlePayment::class);

    $this->travelTo('2026-12-31 23:00');
    $first = ($this->proPayment)();
    $second = ($this->proPayment)();
    $settle->markPaid($first);
    $settle->markPaid($second);

    $this->travelTo('2027-01-01 09:00');
    $third = ($this->proPayment)();
    $settle->markPaid($third);

    expect([$first->fresh()->receipt_number, $second->fresh()->receipt_number, $third->fresh()->receipt_number])
        ->toBe(['RS-2026-000001', 'RS-2026-000002', 'RS-2027-000001']);
});

it('gives the couple a receipt in the vendor name once the vendor confirms a bank transfer', function () {
    Notification::fake();
    $couple = User::factory()->create();
    $booking = Booking::factory()->for($this->vendor)->for($couple)->create(['status' => BookingStatus::PendingPayment, 'total_amount' => 3000]);
    $payment = Payment::factory()->for($booking)->create(['amount' => 900]);

    app(VerifyManualPayment::class)->handle($payment, $this->owner);

    expect($payment->fresh()->receipt_number)->not->toBeNull();
    Notification::assertSentTo($couple, PaymentReceipt::class);

    $this->actingAs($couple)->get(route('payments.document', $payment))
        ->assertOk()
        ->assertSee(__('pages.receipt.receipt'))
        ->assertSee('Studio Seri')
        ->assertSee(__('pages.receipt.footnote_vendor', ['vendor' => 'Studio Seri']))
        ->assertSee('RM2,100.00');
});

it('shows the payer a success page with the receipt', function () {
    $payment = ($this->proPayment)(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'receipt_number' => 'RS-2026-000007', 'details' => ['plan' => 'monthly', 'starts_at' => '2026-09-27', 'ends_at' => '2026-10-27']]);

    $this->actingAs($this->owner)->get(route('payments.show', $payment))
        ->assertOk()
        ->assertSee(__('pages.payment_page.paid_title'))
        ->assertSee(__('pages.payment_page.paid_pro', ['date' => '27 Oktober 2026']))
        ->assertSee('RS-2026-000007')
        ->assertSee(route('payments.document', $payment), false)
        ->assertSee(route('vendor.pro.index'), false);
});

it('tells the payer where a payment stands before it settles, and offers a retry when it failed', function (PaymentStatus $status, string $title, bool $retry) {
    $payment = ($this->proPayment)(['status' => $status]);

    $response = $this->actingAs($this->owner)->get(route('payments.show', $payment))->assertOk()->assertSee(__($title));

    $retry ? $response->assertSee(__('pages.payment_page.retry')) : $response->assertDontSee(__('pages.payment_page.retry'));
})->with([
    'pending' => [PaymentStatus::Pending, 'pages.payment_page.waiting_title', false],
    'failed' => [PaymentStatus::Failed, 'pages.payment_page.failed_title', true],
    'expired' => [PaymentStatus::Expired, 'pages.payment_page.failed_title', true],
]);

it('shows an unpaid payment as an invoice and a paid one as a receipt, with Neekah own details', function () {
    app(InvoiceSettings::class)->save(['company_name' => 'Neekah Sdn. Bhd.', 'registration_no' => '202601012345']);
    $payment = ($this->proPayment)();

    $this->actingAs($this->owner)->get(route('payments.document', $payment))
        ->assertOk()
        ->assertSee(__('pages.receipt.invoice'))
        ->assertSee(__('pages.receipt.amount_due'))
        ->assertSee('Neekah Sdn. Bhd.')
        ->assertSee('202601012345')
        ->assertSee('Studio Seri');

    $payment->update(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'receipt_number' => 'RS-2026-000009']);

    $this->actingAs($this->owner)->get(route('payments.document', $payment))
        ->assertOk()
        ->assertSee(__('pages.receipt.amount_paid'))
        ->assertSee('RS-2026-000009');
});

it('opens a payment only to whoever it concerns', function () {
    $couple = User::factory()->create();
    $partner = User::factory()->create();
    $wedding = Wedding::factory()->for($couple)->create();
    $wedding->members()->attach($partner, ['role' => 'partner']);
    $kenangan = Payment::factory()->kenangan()->create(['wedding_id' => $wedding->id]);
    $pro = ($this->proPayment)();

    $this->get(route('payments.show', $pro))->assertRedirect(route('login'));
    $this->actingAs($partner)->get(route('payments.document', $kenangan))->assertOk();
    $this->actingAs($this->admin)->get(route('payments.show', $pro))->assertOk()->assertSee(route('admin.payments.show', $pro), false);

    $this->actingAs($couple)->get(route('payments.show', $pro))->assertForbidden();
    $this->actingAs(User::factory()->create())->get(route('payments.document', $kenangan))->assertForbidden();
    $this->actingAs(Vendor::factory()->for(Category::first())->create()->user)->get(route('payments.document', $pro))->assertForbidden();
});

it('lets the vendor open the receipt of a deposit paid to them', function () {
    $booking = Booking::factory()->for($this->vendor)->create();
    $payment = Payment::factory()->paid()->for($booking)->create();

    $this->actingAs($this->owner)->get(route('payments.document', $payment))->assertOk();
});

it('emails the whole receipt, with a link to print it', function () {
    $payment = ($this->proPayment)(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'receipt_number' => 'RS-2026-000003', 'details' => ['plan' => 'monthly', 'starts_at' => '2026-09-27', 'ends_at' => '2026-10-27']]);

    $mail = (new PaymentReceipt($payment))->toMail($this->owner);
    $html = (string) $mail->render();

    expect($mail->subject)->toContain('RS-2026-000003')
        ->and($html)->toContain('RS-2026-000003')
        ->and($html)->toContain('Studio Seri')
        ->and($html)->toContain('RM49.00')
        ->and($html)->toContain(e(__('pages.receipt.item_pro_period', ['from' => '27 September 2026', 'to' => '27 Oktober 2026'])))
        ->and($html)->toContain(route('payments.document', $payment));
});

it('lets the admin preview the receipt email and send it again', function () {
    Notification::fake();
    $payment = ($this->proPayment)(['status' => PaymentStatus::Paid, 'paid_at' => now(), 'receipt_number' => 'RS-2026-000004', 'receipt_sent_at' => now()->subDay()]);

    $this->actingAs($this->admin)->get(route('admin.payments.email', $payment))->assertOk()->assertSee('RS-2026-000004');
    $this->actingAs($this->admin)->post(route('admin.payments.receipt', $payment))
        ->assertRedirect()
        ->assertSessionHas('status', __('flash.admin.receipt_sent', ['email' => 'studio@example.com']));

    Notification::assertSentToTimes($this->owner, PaymentReceipt::class, 1);
    expect($payment->fresh()->receipt_sent_at->isToday())->toBeTrue();

    $this->actingAs($this->owner)->get(route('admin.payments.email', $payment))->assertForbidden();
    $this->actingAs($this->admin)->get(route('admin.payments.email', ($this->proPayment)()))->assertNotFound();
});

it('keeps the admin own details for invoices and receipts', function () {
    $this->actingAs($this->admin)->get(route('admin.settings.edit', 'invois'))->assertOk()->assertSee(__('props.admin.invoice_title'));

    $this->actingAs($this->admin)
        ->put(route('admin.settings.invoice'), ['company_name' => ' Neekah Sdn. Bhd. ', 'email' => 'bukan-emel'])
        ->assertSessionHasErrors('email');

    $this->actingAs($this->admin)
        ->put(route('admin.settings.invoice'), ['company_name' => ' Neekah Sdn. Bhd. ', 'tax_no' => 'W10-1234'])
        ->assertSessionHasNoErrors();

    expect(app(InvoiceSettings::class)->companyName())->toBe('Neekah Sdn. Bhd.')
        ->and(app(InvoiceSettings::class)->taxNo())->toBe('W10-1234');

    $this->actingAs($this->owner)->put(route('admin.settings.invoice'), ['company_name' => 'X'])->assertForbidden();
});
