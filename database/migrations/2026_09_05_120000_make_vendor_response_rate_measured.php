<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The column was never computed anywhere: it was seeded, or typed in by an
     * admin, yet shown to couples as a measured fact and fed into the score and
     * the tier ladder. Making it nullable lets a vendor with too few enquiries
     * say "no rate yet" instead of claiming a number nobody measured.
     */
    public function up(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedTinyInteger('response_rate')->nullable()->default(null)->change();
        });
    }

    public function down(): void
    {
        Schema::table('vendors', function (Blueprint $table) {
            $table->unsignedTinyInteger('response_rate')->default(100)->change();
        });
    }
};
