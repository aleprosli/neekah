<?php

namespace App\Console\Commands;

use App\Enums\BookingSource;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\VendorFeature;
use App\Models\Payment;
use App\Models\VendorBookingSetting;
use App\Notifications\CalendarReminder;
use App\Notifications\ManualDepositAwaitingVerification;
use App\Support\OnlineBookingSettings;
use Illuminate\Console\Command;

class SendCalendarReminders extends Command
{
    protected $signature = 'neekah:calendar-reminders';

    protected $description = 'Remind vendors taking online bookings to keep their calendar current, and to check waiting deposits';

    /** Hours a transferred deposit waits for the vendor before they are nudged. */
    public const RECEIPT_WAIT_HOURS = 48;

    /**
     * Couples book straight off the calendar, so a vendor who took a booking
     * on WhatsApp must close that date here. Matched on the calendar day, so
     * a daily run sends each message once: a week after the last
     * confirmation, two days before online booking pauses, and on the day it
     * pauses.
     */
    public function handle(OnlineBookingSettings $site): int
    {
        $fresh = $site->calendarFreshDays();
        $moments = collect([7 => 'weekly', max(1, $fresh - 2) => 'pausing', $fresh => 'paused'])->unique();
        $sent = 0;

        foreach ($moments as $days => $moment) {
            VendorBookingSetting::query()
                ->where('enabled', true)
                ->whereDate('calendar_confirmed_at', today()->subDays($days))
                ->with('vendor.user')
                ->each(function (VendorBookingSetting $settings) use ($moment, &$sent): void {
                    $vendor = $settings->vendor;

                    if ($vendor->isApproved() && $vendor->hasFeature(VendorFeature::OnlineBooking)) {
                        $vendor->user->notify(new CalendarReminder($moment));
                        $sent++;
                    }
                });
        }

        Payment::query()
            ->where('status', PaymentStatus::AwaitingVerification)
            ->whereBetween('created_at', [now()->subHours(self::RECEIPT_WAIT_HOURS + 24), now()->subHours(self::RECEIPT_WAIT_HOURS)])
            ->whereHas('booking', fn ($query) => $query->where('source', BookingSource::Online)->where('status', BookingStatus::PendingPayment))
            ->with('booking.vendor.user')
            ->each(function (Payment $payment) use (&$sent): void {
                $payment->booking->vendor->user->notify(new ManualDepositAwaitingVerification($payment));
                $sent++;
            });

        $this->components->info("{$sent} calendar reminder(s) sent.");

        return self::SUCCESS;
    }
}
