<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20)->default('partner');
            $table->timestamps();

            $table->unique(['wedding_id', 'user_id']);
            $table->index(['user_id', 'wedding_id']);
        });

        // Every existing wedding's creator becomes its owner.
        DB::table('weddings')->orderBy('id')->chunkById(200, function ($weddings): void {
            $now = now();

            DB::table('wedding_members')->insert(
                collect($weddings)->map(fn ($wedding): array => [
                    'wedding_id' => $wedding->id,
                    'user_id' => $wedding->user_id,
                    'role' => 'owner',
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_members');
    }
};
