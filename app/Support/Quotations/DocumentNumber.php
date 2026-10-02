<?php

namespace App\Support\Quotations;

use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

/**
 * A vendor's own running numbers: QT-1001, INV-1001, one sequence per vendor
 * and prefix, so every vendor's first quotation is their QT-1001. Never
 * reused and never taken twice by a race; call inside the transaction that
 * saves the document, so a rollback gives the number back.
 *
 * These are for printing. The public page is found by a random token, never
 * by a number anyone could count through.
 */
class DocumentNumber
{
    public const FIRST = 1001;

    public static function next(Vendor $vendor, string $prefix): string
    {
        $name = 'doc-'.strtolower($prefix).'-'.$vendor->getKey();

        DB::table('sequences')->insertOrIgnore(['name' => $name, 'last' => self::FIRST - 1, 'created_at' => now(), 'updated_at' => now()]);
        $last = (int) DB::table('sequences')->where('name', $name)->lockForUpdate()->value('last') + 1;
        DB::table('sequences')->where('name', $name)->update(['last' => $last, 'updated_at' => now()]);

        return $prefix.'-'.$last;
    }
}
