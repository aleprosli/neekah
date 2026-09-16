<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Payments are recorded by hand now: the couple pays the vendor directly and
     * enters what they paid, with a receipt, and the vendor confirms it arrived.
     *
     * The deposit and balance instalments go with it. Nobody agreed to a 40/60
     * split — it was the platform's invention, and it put two amounts on the
     * page that neither side had discussed. The gateway columns stay for a real
     * gateway later.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->foreignId('recorded_by')->nullable()->after('booking_id')->constrained('users')->nullOnDelete();
            $table->date('paid_on')->nullable()->after('amount');
            $table->string('method')->default('manual_transfer')->after('paid_on');
            $table->string('receipt_image')->nullable()->after('method');
            $table->string('note')->nullable()->after('receipt_image');
            $table->timestamp('verified_at')->nullable()->after('paid_at');
            $table->foreignId('verified_by')->nullable()->after('verified_at')->constrained('users')->nullOnDelete();
            // The replacement index goes in first: booking_id's foreign key
            // needs one at all times, and SQLite refuses to drop a column that
            // an index still names.
            $table->index(['booking_id', 'status']);
            $table->dropIndex(['booking_id', 'type']);
            $table->dropColumn('type');
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('deposit_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('recorded_by');
            $table->dropConstrainedForeignId('verified_by');
            $table->dropColumn(['paid_on', 'method', 'receipt_image', 'note', 'verified_at']);
            $table->string('type')->default('deposit')->after('booking_id');
            $table->index(['booking_id', 'type']);
            $table->dropIndex(['booking_id', 'status']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('deposit_amount', 10, 2)->default(0)->after('total_amount');
        });
    }
};
