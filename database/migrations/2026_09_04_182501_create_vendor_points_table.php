<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 40);
            $table->integer('points');
            $table->nullableMorphs('pointable');
            $table->timestamps();

            $table->unique(['vendor_id', 'reason', 'pointable_type', 'pointable_id'], 'vendor_points_unique_award');
            $table->index(['vendor_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_points');
    }
};
