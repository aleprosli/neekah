<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kamera Majlis: one shared album per wedding, opened by buying a tier.
     * Guests reach it through the unguessable token in its QR; the counters
     * are what the tier's limits are checked against, and bytes_reserved holds
     * uploads still on their way so two phones cannot race past a cap.
     */
    public function up(): void
    {
        Schema::create('camera_albums', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 16)->unique();
            $table->string('tier', 10);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('purged_at')->nullable();
            $table->string('title')->nullable();
            $table->string('welcome_message', 300)->nullable();
            $table->string('passcode_hash')->nullable();
            $table->unsignedInteger('passcode_version')->default(0);
            $table->boolean('guests_can_view')->default(true);
            $table->boolean('uploads_open')->default(true);
            $table->unsignedInteger('photos_count')->default(0);
            $table->unsignedInteger('videos_count')->default(0);
            $table->unsignedInteger('reserved_count')->default(0);
            $table->unsignedBigInteger('bytes_used')->default(0);
            $table->unsignedBigInteger('bytes_reserved')->default(0);
            $table->string('qr_design', 20)->default('ikut-kad');
            $table->json('qr_options')->nullable();
            $table->json('export_paths')->nullable();
            $table->timestamp('exported_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_albums');
    }
};
