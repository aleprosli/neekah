<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The section text a vendor's new contracts start with. */
    public function up(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->json('contract_defaults')->nullable()->after('quotation_terms');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->dropColumn('contract_defaults');
        });
    }
};
