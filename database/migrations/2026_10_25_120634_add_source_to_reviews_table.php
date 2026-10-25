<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** The partner a review came in through (e.g. kampung1998), or null for Neekah itself. */
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->string('source', 32)->nullable()->after('added_by');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('source');
        });
    }
};
