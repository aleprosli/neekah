<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->integer('points_total')->default(0)->after('score');
            $table->unsignedTinyInteger('completion_rate')->default(100)->after('response_rate');
            $table->boolean('tier_locked')->default(false)->after('tier');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['points_total', 'completion_rate', 'tier_locked']);
        });
    }
};
