<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_rsvps', function (Blueprint $table) {
            $table->timestamp('message_approved_at')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('wedding_rsvps', function (Blueprint $table) {
            $table->dropColumn('message_approved_at');
        });
    }
};
