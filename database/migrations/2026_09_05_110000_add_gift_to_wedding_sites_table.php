<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_sites', function (Blueprint $table) {
            $table->boolean('gift_enabled')->default(false)->after('rsvp_deadline');
            $table->text('gift_note')->nullable()->after('gift_enabled');
            $table->string('gift_qr_image')->nullable()->after('gift_note');
            $table->json('gift_accounts')->nullable()->after('gift_qr_image');
            $table->boolean('wishes_enabled')->default(true)->after('gift_accounts');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_sites', function (Blueprint $table) {
            $table->dropColumn(['gift_enabled', 'gift_note', 'gift_qr_image', 'gift_accounts', 'wishes_enabled']);
        });
    }
};
