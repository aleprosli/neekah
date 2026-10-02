<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The terms a vendor's new quotations start with. */
    public function up(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->text('quotation_terms')->nullable()->after('deposit_terms');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->dropColumn('quotation_terms');
        });
    }
};
