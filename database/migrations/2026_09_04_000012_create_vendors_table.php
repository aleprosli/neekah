<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('city');
            $table->string('state', 40);
            $table->string('phone', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->decimal('price_from', 10, 2)->default(0);
            $table->string('price_unit', 20)->default('package');
            $table->string('cover_image')->nullable();
            $table->string('cover_tone', 80)->default('from-rose-400 to-amber-300');
            $table->string('status', 20)->default('pending');
            $table->string('tier', 20)->default('new');
            $table->decimal('rating_avg', 3, 2)->default(0);
            $table->unsignedInteger('reviews_count')->default(0);
            $table->unsignedInteger('completed_bookings_count')->default(0);
            $table->unsignedTinyInteger('response_rate')->default(100);
            $table->decimal('score', 6, 2)->default(0);
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'score']);
            $table->index(['status', 'category_id']);
            $table->index(['status', 'state']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
};
