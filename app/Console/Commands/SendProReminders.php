<?php

namespace App\Console\Commands;

use App\Models\Vendor;
use App\Notifications\ProExpiring;
use Illuminate\Console\Command;

class SendProReminders extends Command
{
    protected $signature = 'neekah:pro-reminders';

    protected $description = 'Remind vendors a week and a day before their Pro plan runs out';

    /** Days before the end on which a vendor is reminded. */
    private const DAYS_BEFORE = [7, 1];

    /**
     * FPX has no auto-debit, so Pro only continues if the vendor pays again.
     * Matched on the calendar day, so running once a day sends each reminder
     * once.
     */
    public function handle(): int
    {
        $sent = 0;

        foreach (self::DAYS_BEFORE as $days) {
            Vendor::query()
                ->approved()
                ->with('user')
                ->whereDate('pro_until', today()->addDays($days))
                ->each(function (Vendor $vendor) use ($days, &$sent): void {
                    $vendor->user->notify(new ProExpiring($vendor, $days));
                    $sent++;
                });
        }

        $this->components->info("{$sent} Pro reminder(s) sent.");

        return self::SUCCESS;
    }
}
