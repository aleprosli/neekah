<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One Kamera Majlis purchase: a checkout on Neekah's Herepay account, or
     * one an admin recorded by hand. Only a paid one opens or upgrades the
     * album, and ActivateCameraAlbum is the only thing that marks one paid.
     */
    public function up(): void
    {
        Schema::create('camera_purchases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('tier', 10);
            $table->string('kind', 10)->default('new');
            $table->decimal('amount', 10, 2);
            $table->string('status')->default('pending')->index();
            $table->string('gateway', 20);
            $table->string('gateway_reference')->nullable()->index();
            $table->string('payment_url', 2048)->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_purchases');
    }
};
