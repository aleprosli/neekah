<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A photo's "-display" copy, the size the full-screen viewer shows, so
     * opening a photo does not download a Pro album's original. Photos
     * processed before it existed have none and the viewer falls back to the
     * original.
     */
    public function up(): void
    {
        Schema::table('camera_media', function (Blueprint $table) {
            $table->string('display_path')->nullable()->after('path');
        });
    }

    public function down(): void
    {
        Schema::table('camera_media', function (Blueprint $table) {
            $table->dropColumn('display_path');
        });
    }
};
