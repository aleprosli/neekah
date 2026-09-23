<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_songs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('moment', 30);
            $table->string('title');
            $table->string('artist')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'moment']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_songs');
    }
};
