<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Neekah went free for everyone on 18 Sep 2026 (Booking::COMMISSION_RATE = 0).
 * The owner chose to clear the commission on the bookings made before that as
 * well, so no vendor keeps seeing an 8% deduction. No money moves through
 * Neekah, so the figure was only ever on paper; nothing was collected.
 *
 * On production this touched three bookings, one vendor, RM192 in total. A
 * full database backup was taken first
 * (~/backups/neekah-20260918-230411-before-commission-zero.sql).
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('bookings')
            ->where('commission_rate', '>', 0)
            ->update(['commission_rate' => 0, 'commission_amount' => 0]);
    }

    /**
     * The old figures are not kept anywhere but the backup, so there is
     * nothing to put back from here.
     */
    public function down(): void
    {
        //
    }
};
