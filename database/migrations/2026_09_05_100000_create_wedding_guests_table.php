<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->string('phone', 30)->nullable();
            $table->string('phone_normalised', 20)->nullable();
            $table->string('side', 10)->default('both');
            $table->string('group', 20)->default('other');
            $table->unsignedSmallInteger('pax_invited')->default(1);
            $table->text('notes')->nullable();
            $table->string('token', 16)->unique();
            $table->timestamp('shared_at')->nullable();
            $table->timestamp('first_opened_at')->nullable();
            $table->timestamp('last_opened_at')->nullable();
            $table->unsignedInteger('open_count')->default(0);
            $table->timestamps();

            $table->index(['wedding_id', 'side']);
            $table->index(['wedding_id', 'group']);
            $table->index(['wedding_id', 'phone_normalised']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_guests');
    }
};
