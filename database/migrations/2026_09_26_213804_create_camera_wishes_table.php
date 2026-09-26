<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A guest's wish for the couple, left through the album's QR: a written
     * message on every tier, or a short voice recording on Pro. Only the
     * couple sees them. The recording lives under the album's directory, so
     * purging the album takes it too.
     */
    public function up(): void
    {
        Schema::create('camera_wishes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_album_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->text('message')->nullable();
            $table->string('audio_path')->nullable();
            $table->string('mime', 60)->nullable();
            $table->unsignedInteger('bytes')->default(0);
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->string('guest_name', 40)->nullable();
            $table->char('device_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['camera_album_id', 'created_at']);
            $table->index(['camera_album_id', 'device_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_wishes');
    }
};
