<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The library of background tracks a couple may play on their card. Admin uploads
 * them, so nothing is hotlinked and nothing unlicensed arrives from a guest.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_music_tracks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('path');
            $table->unsignedSmallInteger('seconds')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_music_tracks');
    }
};
