<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_site_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_site_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption', 160)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['wedding_site_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_site_photos');
    }
};
