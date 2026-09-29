<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A wedding can hold several albums, one per majlis (akad nikah, sanding,
     * bertandang), each bought on its own. An album may keep its own date;
     * without one it follows the wedding's. A purchase now names the album it
     * opened or upgraded, and a new one carries the title and date the couple
     * gave it until the payment creates the album.
     */
    public function up(): void
    {
        Schema::table('camera_albums', function (Blueprint $table) {
            // The foreign key needs an index of its own before the unique one goes.
            $table->index('wedding_id');
        });

        Schema::table('camera_albums', function (Blueprint $table) {
            $table->dropUnique(['wedding_id']);
            $table->date('event_date')->nullable()->after('title');
        });

        Schema::table('camera_purchases', function (Blueprint $table) {
            $table->foreignId('camera_album_id')->nullable()->after('wedding_id')->constrained()->nullOnDelete();
            $table->string('album_title', 120)->nullable()->after('kind');
            $table->date('album_event_date')->nullable()->after('album_title');
        });

        DB::table('camera_purchases')
            ->whereNull('camera_album_id')
            ->update(['camera_album_id' => DB::table('camera_albums')
                ->select('id')
                ->whereColumn('camera_albums.wedding_id', 'camera_purchases.wedding_id')
                ->limit(1)]);
    }

    public function down(): void
    {
        Schema::table('camera_purchases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('camera_album_id');
            $table->dropColumn(['album_title', 'album_event_date']);
        });

        Schema::table('camera_albums', function (Blueprint $table) {
            $table->dropColumn('event_date');
            $table->unique('wedding_id');
            $table->dropIndex(['wedding_id']);
        });
    }
};
