<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedSmallInteger('penalty_points')->default(0)->after('score');
            $table->unsignedTinyInteger('violations_count')->default(0)->after('penalty_points');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn(['penalty_points', 'violations_count']);
        });
    }
};
