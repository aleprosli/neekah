<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_rsvps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone', 30)->nullable();
            $table->boolean('attending')->default(true);
            $table->unsignedSmallInteger('pax')->default(1);
            $table->text('message')->nullable();
            $table->timestamps();

            $table->index(['wedding_site_id', 'attending']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_rsvps');
    }
};
