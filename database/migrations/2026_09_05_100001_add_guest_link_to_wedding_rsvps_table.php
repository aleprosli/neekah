<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_rsvps', function (Blueprint $table) {
            $table->foreignId('wedding_guest_id')->nullable()->after('wedding_site_id')->constrained()->nullOnDelete();
            $table->string('matched_by', 10)->nullable()->after('wedding_guest_id');
            $table->string('phone_normalised', 20)->nullable()->after('phone');
            $table->boolean('counted')->default(true)->after('pax');

            $table->unique('wedding_guest_id');
            $table->index(['wedding_site_id', 'phone_normalised']);
        });
    }

    public function down(): void
    {
        Schema::table('wedding_rsvps', function (Blueprint $table) {
            $table->dropIndex(['wedding_site_id', 'phone_normalised']);
            $table->dropUnique(['wedding_guest_id']);
            $table->dropConstrainedForeignId('wedding_guest_id');
            $table->dropColumn(['matched_by', 'phone_normalised', 'counted']);
        });
    }
};
