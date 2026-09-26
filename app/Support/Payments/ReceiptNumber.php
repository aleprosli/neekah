<?php

namespace App\Support\Payments;

use Illuminate\Support\Facades\DB;

/**
 * Running receipt numbers: RS-2026-000001, one sequence per year, never reused
 * and never skipped by a race. Call inside the transaction that marks the
 * payment paid, so a rolled-back payment gives its number back.
 */
class ReceiptNumber
{
    public static function next(?int $year = null): string
    {
        $year ??= (int) now()->format('Y');
        $name = 'receipt-'.$year;

        DB::table('sequences')->insertOrIgnore(['name' => $name, 'last' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $last = (int) DB::table('sequences')->where('name', $name)->lockForUpdate()->value('last') + 1;
        DB::table('sequences')->where('name', $name)->update(['last' => $last, 'updated_at' => now()]);

        return sprintf('RS-%d-%06d', $year, $last);
    }
}
