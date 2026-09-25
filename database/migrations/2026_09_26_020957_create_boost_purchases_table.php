<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A pack of boost tokens a vendor buys on Neekah's own Herepay account.
     * Only ActivateBoostPurchase marks one paid and credits the tokens.
     */
    public function up(): void
    {
        Schema::create('boost_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference', 20)->unique();
            $table->string('pack', 20);
            $table->unsignedInteger('tokens');
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending');
            $table->string('gateway', 20);
            $table->string('gateway_reference')->nullable();
            $table->text('payment_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('boost_purchases');
    }
};
