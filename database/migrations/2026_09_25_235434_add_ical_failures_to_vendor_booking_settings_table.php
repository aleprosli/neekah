<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Imports that failed in a row, so the vendor is told once when their
     * Google Calendar link stops working, not every hour.
     */
    public function up(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->unsignedSmallInteger('ical_failures')->default(0)->after('ical_error');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->dropColumn('ical_failures');
        });
    }
};
