<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The card designs stop being Blade layouts and become composed layer stacks.
 *
 * A design is now a palette, four type faces and three canvases of absolutely
 * positioned layers, all held on the row. The old `design` column described a
 * Blade partial that no longer exists, so it goes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_templates', function (Blueprint $table) {
            $table->string('category', 40)->after('style')->default('Traditional');
            $table->boolean('is_premium')->after('category')->default(false);
            // The ten colour roles and the four type faces the design is painted with.
            $table->json('palette')->nullable()->after('description');
            $table->json('fonts')->nullable()->after('palette');
            // cover / invitation / event, each a list of layers.
            $table->json('scenes')->nullable()->after('fonts');
            // Which photos this design asks the couple for.
            $table->json('photo_slots')->nullable()->after('scenes');
            $table->dropColumn('design');
        });

        // Every old slug is gone with its Blade partial, so point existing cards at
        // the flagship design; the seeder creates it immediately after this runs.
        DB::table('wedding_sites')->update(['template' => 'neekah-signature']);
        DB::table('site_templates')->delete();
    }

    public function down(): void
    {
        Schema::table('site_templates', function (Blueprint $table) {
            $table->json('design')->nullable();
            $table->dropColumn(['category', 'is_premium', 'palette', 'fonts', 'scenes', 'photo_slots']);
        });
    }
};
