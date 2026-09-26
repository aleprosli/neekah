<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Each Pro purchase, paid or not. The amount is stamped at checkout, so a price
 * the admin changes later never rewrites what a vendor was charged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('reference')->unique();
            $table->string('plan');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending')->index();
            // 'herepay' for a checkout, 'manual' for one an admin recorded.
            $table->string('gateway');
            $table->string('gateway_reference')->nullable()->index();
            $table->string('payment_url', 2048)->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_subscriptions');
    }
};
