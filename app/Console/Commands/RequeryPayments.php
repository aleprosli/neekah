<?php

namespace App\Console\Commands;

use App\Actions\RequeryPayment;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Illuminate\Console\Command;

class RequeryPayments extends Command
{
    protected $signature = 'neekah:requery-payments';

    protected $description = 'Ask the gateway about online payments still pending, and close the links that ran out';

    /** Give the callback this long before asking ourselves. */
    public const AFTER_MINUTES = 10;

    /** Ask again at most this often per payment. */
    public const EVERY_MINUTES = 10;

    /** Stop asking after this; a link lives a day. */
    public const FOR_DAYS = 3;

    /** A link this long past its expiry, still unpaid, is closed as expired. */
    public const EXPIRE_AFTER_MINUTES = 60;

    /**
     * A callback can be lost (our server down, theirs retrying and giving
     * up). Every pending online payment whose gateway code we know is asked about
     * again (by its payment code) until it settles or grows too old; one whose link ran out long
     * ago is marked expired, so the ledger does not keep it pending forever.
     */
    public function handle(RequeryPayment $requery): int
    {
        $asked = 0;

        Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->whereNot('gateway', Payment::GATEWAY_MANUAL)
            ->where(fn ($query) => $query->whereNotNull('gateway_reference')->orWhereNotNull('gateway_invoice'))
            ->where('created_at', '<', now()->subMinutes(self::AFTER_MINUTES))
            ->where('created_at', '>', now()->subDays(self::FOR_DAYS))
            ->where(fn ($query) => $query->whereNull('last_checked_at')->orWhere('last_checked_at', '<', now()->subMinutes(self::EVERY_MINUTES)))
            ->chunkById(50, function ($payments) use ($requery, &$asked): void {
                foreach ($payments as $payment) {
                    if ($requery->handle($payment) !== 'unavailable') {
                        $asked++;
                    }
                }
            });

        $expired = Payment::query()
            ->where('status', PaymentStatus::Pending)
            ->whereNot('gateway', Payment::GATEWAY_MANUAL)
            ->where('expires_at', '<', now()->subMinutes(self::EXPIRE_AFTER_MINUTES))
            ->update(['status' => PaymentStatus::Expired]);

        $this->components->info("{$asked} payment(s) asked about, {$expired} expired.");

        return self::SUCCESS;
    }
}
