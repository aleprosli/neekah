<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * An online booking holds its date until the deposit is paid. The deposit
     * is the vendor's own rule, stamped when the booking is made; bookings the
     * vendor records by hand have none.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->nullable()->after('total_amount');
            $table->string('source', 20)->default('vendor')->after('status');
            $table->string('payment_mode', 20)->nullable()->after('source');
            $table->timestamp('hold_expires_at')->nullable()->after('payment_mode');
            $table->string('cancelled_reason', 20)->nullable()->after('cancelled_at');

            $table->index(['status', 'hold_expires_at']);
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex(['status', 'hold_expires_at']);
            $table->dropColumn(['deposit_amount', 'source', 'payment_mode', 'hold_expires_at', 'cancelled_reason']);
        });
    }
};
