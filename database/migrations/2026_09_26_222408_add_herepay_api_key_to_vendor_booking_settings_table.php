<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A vendor's Herepay API key (XApiKey), encrypted like their other keys.
     * Optional: without it their deposits still work, only asking Herepay
     * again about one that never called back does not.
     */
    public function up(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->text('herepay_api_key')->nullable()->after('herepay_private_key');
        });
    }

    public function down(): void
    {
        Schema::table('vendor_booking_settings', function (Blueprint $table) {
            $table->dropColumn('herepay_api_key');
        });
    }
};
