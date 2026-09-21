<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * What the couple changed about the template they picked, and how they
     * arranged the card's sections. Null in both means "the template exactly
     * as it was designed", which is what every existing card is.
     */
    public function up(): void
    {
        Schema::table('wedding_sites', function (Blueprint $table) {
            $table->json('design_overrides')->nullable()->after('template');
            $table->json('sections')->nullable()->after('design_overrides');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_sites', function (Blueprint $table) {
            $table->dropColumn(['design_overrides', 'sections']);
        });
    }
};
