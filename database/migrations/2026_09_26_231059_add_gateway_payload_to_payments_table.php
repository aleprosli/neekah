<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The last thing the gateway sent or answered about a payment, kept on
     * the payment itself as JSON so it can be read straight off the row:
     * where it came from (callback, return, requery), whether it verified,
     * when, and the data exactly as received. The full history stays in
     * payment_events.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->json('gateway_payload')->nullable()->after('gateway_status');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('gateway_payload');
        });
    }
};
