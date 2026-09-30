<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * When the vendor's paid Pro plan runs out. The subscriptions table is the
 * history; this is the one column the listing, the badge and the dashboard read,
 * so none of them has to join through the payments to answer "is this Pro?".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->timestamp('pro_until')->nullable()->after('approved_at')->index();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['pro_until']);
            $table->dropColumn('pro_until');
        });
    }
};
