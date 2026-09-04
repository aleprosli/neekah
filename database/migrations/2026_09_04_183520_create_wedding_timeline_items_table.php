<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_timeline_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->time('starts_at');
            $table->time('ends_at')->nullable();
            $table->string('title');
            $table->text('notes')->nullable();
            $table->string('location')->nullable();
            $table->timestamps();

            $table->index(['wedding_id', 'starts_at']);
            $table->index(['vendor_id', 'wedding_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_timeline_items');
    }
};
