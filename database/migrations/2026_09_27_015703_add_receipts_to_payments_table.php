<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A paid payment gets a receipt: a running number (RS-2026-000001, one
     * sequence a year, never reused) and when it was emailed. The number is
     * drawn from `sequences` under a row lock, so two payments settling at
     * once cannot take the same one. Until it is paid, the same document is
     * the invoice, numbered by the payment's reference.
     */
    public function up(): void
    {
        Schema::create('sequences', function (Blueprint $table) {
            $table->string('name', 40)->primary();
            $table->unsignedBigInteger('last')->default(0);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_number', 30)->nullable()->unique()->after('reference');
            $table->timestamp('receipt_sent_at')->nullable()->after('paid_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['receipt_number']);
            $table->dropColumn(['receipt_number', 'receipt_sent_at']);
        });

        Schema::dropIfExists('sequences');
    }
};
