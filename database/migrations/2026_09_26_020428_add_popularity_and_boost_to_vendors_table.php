<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * views_30d and trending_at are filled nightly by neekah:vendor-popularity
     * for the "Paling ramai dilihat" sort and the Trending badge; they never
     * feed the score. boost_tokens is the balance GrantBoostTokens keeps.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedInteger('views_30d')->default(0)->index()->after('score');
            $table->timestamp('trending_at')->nullable()->after('views_30d');
            $table->unsignedInteger('boost_tokens')->default(0)->after('trending_at');
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->dropIndex(['views_30d']);
            $table->dropColumn(['views_30d', 'trending_at', 'boost_tokens']);
        });
    }
};
