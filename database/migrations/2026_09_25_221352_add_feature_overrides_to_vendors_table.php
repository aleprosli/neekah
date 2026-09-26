<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-vendor exceptions to what their plan opens: {feature: true|false}.
     * A feature not listed follows the plan.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('feature_overrides')->nullable()->after('pro_until');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('feature_overrides');
        });
    }
};
