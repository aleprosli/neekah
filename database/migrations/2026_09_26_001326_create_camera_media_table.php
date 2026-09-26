<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A photo or video a guest put in the album. It is reserved first (its
     * place counted), uploaded straight to storage, then processed; only a
     * ready one is shown. The guest is known by a hashed device cookie and an
     * optional name, never an account.
     */
    public function up(): void
    {
        Schema::create('camera_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('camera_album_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->string('status', 12);
            $table->string('incoming_path')->nullable();
            $table->string('path')->nullable();
            $table->string('poster_path')->nullable();
            $table->unsignedBigInteger('bytes')->default(0);
            $table->unsignedBigInteger('declared_bytes')->default(0);
            $table->string('mime', 60)->nullable();
            $table->unsignedSmallInteger('width')->nullable();
            $table->unsignedSmallInteger('height')->nullable();
            $table->unsignedSmallInteger('duration_seconds')->nullable();
            $table->string('uploader_name', 40)->nullable();
            $table->char('device_hash', 64)->nullable();
            $table->timestamps();

            $table->index(['camera_album_id', 'status', 'created_at']);
            $table->index(['camera_album_id', 'device_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_media');
    }
};
