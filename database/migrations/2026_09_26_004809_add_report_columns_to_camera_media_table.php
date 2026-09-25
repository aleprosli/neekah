<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A guest can report a photo or video in a Kamera Majlis album; it waits
     * on the admin page until it is deleted or the report is dismissed.
     */
    public function up(): void
    {
        Schema::table('camera_media', function (Blueprint $table) {
            $table->timestamp('reported_at')->nullable()->index()->after('device_hash');
            $table->string('report_reason', 300)->nullable()->after('reported_at');
        });
    }

    public function down(): void
    {
        Schema::table('camera_media', function (Blueprint $table) {
            $table->dropColumn(['reported_at', 'report_reason']);
        });
    }
};
