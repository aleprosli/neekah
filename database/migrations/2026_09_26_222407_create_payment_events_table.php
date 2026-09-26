<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Everything that passed between Neekah and a payment gateway, kept as it
     * arrived: the link we asked for and what came back, every callback,
     * every return of the payer, every requery, and what an admin did by hand.
     * Rows are only ever added. A callback that names no payment we know is
     * still kept (payment_id null), since that is exactly the one worth
     * looking at later. Keys and checksums' secrets are never stored.
     */
    public function up(): void
    {
        Schema::create('payment_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway', 30);
            $table->string('type', 30);
            $table->boolean('verified')->nullable();
            $table->string('outcome', 20)->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['payment_id', 'created_at']);
            $table->index(['gateway', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_events');
    }
};
