<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Vendor features are fixed per plan now (VendorFeature::requiresPro), so
     * the per-vendor overrides and the per-plan settings go.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropColumn('feature_overrides');
        });

        DB::table('settings')->where('key', 'like', 'vendor_features.%')->delete();
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->json('feature_overrides')->nullable()->after('pro_until');
        });
    }
};
