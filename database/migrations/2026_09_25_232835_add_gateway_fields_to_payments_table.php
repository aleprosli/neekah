<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A deposit paid through the vendor's Herepay account: the link the
     * couple pays on, reused while it is still open.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->text('payment_url')->nullable();
            $table->timestamp('expires_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payment_url', 'expires_at']);
        });
    }
};
