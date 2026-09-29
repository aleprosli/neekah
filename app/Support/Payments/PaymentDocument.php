<?php

namespace App\Support\Payments;

use App\Enums\CameraTier;
use App\Enums\PaymentMerchant;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Enums\VendorPlan;
use App\Models\Payment;
use App\Models\User;
use App\Support\InvoiceSettings;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * The invoice or receipt for one payment, as the payer, the admin and the
 * receipt email all see it. Unpaid, it is an invoice; paid, it is a receipt
 * with its own number. Neekah issues it for what Neekah sold (Pro, boost,
 * Kenangan); a booking payment is issued in the vendor's name, because the
 * money went to the vendor's own account and Neekah only recorded it.
 */
class PaymentDocument
{
    public function __construct(public readonly Payment $payment, private readonly InvoiceSettings $settings)
    {
        $payment->loadMissing(['vendor.user', 'booking.user', 'booking.vendor', 'wedding.user', 'album', 'recorder']);
    }

    public static function for(Payment $payment): self
    {
        return new self($payment, app(InvoiceSettings::class));
    }

    /**
     * Who the receipt goes to: the vendor who bought Pro or boost, the couple
     * who paid for Kenangan or a booking.
     */
    public static function payerOf(Payment $payment): ?User
    {
        return match ($payment->purpose) {
            PaymentPurpose::VendorPro, PaymentPurpose::BoostTokens => $payment->vendor?->user,
            PaymentPurpose::Kenangan => $payment->recorder?->isAdmin() === false ? $payment->recorder : $payment->wedding?->user,
            PaymentPurpose::Booking => $payment->booking?->user,
        };
    }

    public function isReceipt(): bool
    {
        return $this->payment->isPaid() || $this->payment->status === PaymentStatus::Refunded;
    }

    public function title(): string
    {
        return __($this->isReceipt() ? 'pages.receipt.receipt' : 'pages.receipt.invoice');
    }

    /** The receipt number once paid; the payment reference until then. */
    public function number(): string
    {
        return $this->isReceipt() && $this->payment->receipt_number ? $this->payment->receipt_number : $this->payment->reference;
    }

    public function issuedByVendor(): bool
    {
        return $this->payment->merchant === PaymentMerchant::VENDOR;
    }

    /**
     * @return array{name: string, lines: list<string>}
     */
    public function issuer(): array
    {
        if ($this->issuedByVendor()) {
            $vendor = $this->payment->vendor ?? $this->payment->booking?->vendor;

            return [
                'name' => $vendor?->name ?? '—',
                'lines' => array_values(array_filter([
                    collect([$vendor?->city, $vendor?->state])->filter()->implode(', '),
                    $vendor?->phone,
                    $vendor?->user?->email,
                ])),
            ];
        }

        return [
            'name' => $this->settings->companyName(),
            'lines' => array_values(array_filter([
                $this->settings->registrationNo() ? __('pages.receipt.registration_no', ['number' => $this->settings->registrationNo()]) : null,
                $this->settings->address(),
                collect([$this->settings->phone(), $this->settings->email()])->filter()->implode(' · '),
                $this->settings->taxNo() ? __('pages.receipt.tax_no', ['number' => $this->settings->taxNo()]) : null,
            ])),
        ];
    }

    /**
     * @return array{name: string, lines: list<string>}
     */
    public function billTo(): array
    {
        $payer = self::payerOf($this->payment);
        $business = in_array($this->payment->purpose, [PaymentPurpose::VendorPro, PaymentPurpose::BoostTokens], true) ? $this->payment->vendor : null;

        return [
            'name' => $business?->name ?? $payer?->name ?? '—',
            'lines' => array_values(array_filter([
                $business ? $payer?->name : null,
                $payer?->email,
                $payer?->phone ?? $business?->phone,
            ])),
        ];
    }

    /**
     * What was paid for, one line each.
     *
     * @return list<array{description: string, detail: ?string, amount: string}>
     */
    public function items(): array
    {
        $payment = $this->payment;

        $item = match ($payment->purpose) {
            PaymentPurpose::VendorPro => [
                'description' => __('pages.receipt.item_pro', ['plan' => VendorPlan::tryFrom((string) $payment->detail('plan'))?->label() ?? '']),
                'detail' => $payment->detail('ends_at') ? __('pages.receipt.item_pro_period', [
                    'from' => self::date($payment->detail('starts_at')),
                    'to' => self::date($payment->detail('ends_at')),
                ]) : null,
            ],
            PaymentPurpose::BoostTokens => [
                'description' => __('pages.receipt.item_boost', ['pack' => Str::headline((string) $payment->detail('pack'))]),
                'detail' => __('pages.receipt.item_boost_tokens', ['count' => (int) $payment->detail('tokens', 0)]),
            ],
            PaymentPurpose::Kenangan => [
                'description' => __('pages.receipt.item_kenangan_'.($payment->detail('kind') === 'upgrade' ? 'upgrade' : 'new'), [
                    'tier' => CameraTier::tryFrom((string) $payment->detail('tier'))?->label() ?? '',
                ]),
                'detail' => collect([
                    $payment->album?->displayTitle() ?? $payment->detail('album_title') ?? $payment->wedding?->title,
                    ($date = $payment->album?->event_date ?? $payment->detail('album_event_date')) ? self::date($date) : null,
                ])->filter()->implode(' · ') ?: null,
            ],
            PaymentPurpose::Booking => [
                'description' => __('pages.receipt.item_booking', ['package' => $payment->booking?->package_name ?? '—']),
                'detail' => $payment->booking ? __('pages.receipt.item_booking_detail', [
                    'reference' => $payment->booking->reference,
                    'date' => $payment->booking->event_date->translatedFormat('j F Y'),
                ]) : null,
            ],
        };

        return [[...$item, 'amount' => self::ringgit((float) $payment->amount)]];
    }

    public function total(): string
    {
        return self::ringgit((float) $this->payment->amount);
    }

    /**
     * Where the booking stands after this payment: its price, what has been
     * paid so far and what is left, so the receipt answers "how much more?".
     *
     * @return array<string, string>
     */
    public function bookingSummary(): array
    {
        $booking = $this->payment->booking;

        if (! $booking || ! $booking->total_amount) {
            return [];
        }

        return [
            __('pages.receipt.booking_total') => self::ringgit((float) $booking->total_amount),
            __('pages.receipt.booking_paid') => self::ringgit($booking->paidAmount()),
            __('pages.receipt.booking_balance') => self::ringgit($booking->outstandingAmount()),
        ];
    }

    /**
     * The facts beside the number: dates, how it was paid, the gateway's
     * references.
     *
     * @return array<string, string>
     */
    public function facts(): array
    {
        $payment = $this->payment;

        return array_filter([
            __('pages.receipt.reference') => $payment->reference,
            __('pages.receipt.issued_on') => $payment->created_at->translatedFormat('j F Y'),
            // A bank transfer is known by its day only; a gateway says the minute.
            __('pages.receipt.paid_on') => $this->isReceipt() ? ($payment->paid_at ?? $payment->paid_on)?->translatedFormat($payment->isOnline() ? 'j F Y, g:i A' : 'j F Y') : null,
            __('pages.receipt.method') => $this->method(),
            __('pages.receipt.gateway_reference') => $payment->gateway_reference,
            __('pages.receipt.transaction_id') => $payment->gateway_transaction_id,
        ], fn (?string $value): bool => filled($value));
    }

    public function method(): string
    {
        if (! $this->payment->isOnline()) {
            return __('pages.receipt.method_transfer');
        }

        return collect([Str::headline($this->payment->gateway), $this->payment->method])->filter()->implode(' · ');
    }

    public function statusLabel(): string
    {
        return $this->payment->status->label();
    }

    public function statusTone(): string
    {
        return $this->payment->status->tone();
    }

    /** The line at the foot: whose account took the money, and the admin's own note. */
    public function footnote(): string
    {
        return $this->issuedByVendor()
            ? __('pages.receipt.footnote_vendor', ['vendor' => $this->issuer()['name']])
            : ($this->settings->note() ?: __('pages.receipt.footnote_neekah'));
    }

    private static function date(mixed $value): string
    {
        return Carbon::parse($value)->translatedFormat('j F Y');
    }

    private static function ringgit(float $amount): string
    {
        return 'RM'.number_format($amount, 2);
    }
}
