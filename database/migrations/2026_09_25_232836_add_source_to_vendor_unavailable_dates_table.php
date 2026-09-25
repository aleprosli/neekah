<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Where a closed date came from (closed by hand, or imported from the
     * vendor's Google Calendar), so an import can replace its own rows without
     * touching the ones the vendor closed. `slots` is set when an outside
     * booking takes one place on a day that holds several; null closes it.
     *
     * The new unique index goes in before the old one comes out: on MySQL the
     * old one also serves the vendor_id foreign key.
     */
    public function up(): void
    {
        Schema::table('vendor_unavailable_dates', function (Blueprint $table) {
            $table->string('source', 20)->default('manual')->after('reason');
            $table->string('external_uid')->nullable()->after('source');
            $table->unsignedTinyInteger('slots')->nullable()->after('external_uid');
            $table->unique(['vendor_id', 'date', 'source']);
        });

        Schema::table('vendor_unavailable_dates', function (Blueprint $table) {
            $table->dropUnique(['vendor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::table('vendor_unavailable_dates', function (Blueprint $table) {
            $table->unique(['vendor_id', 'date']);
        });

        Schema::table('vendor_unavailable_dates', function (Blueprint $table) {
            $table->dropUnique(['vendor_id', 'date', 'source']);
            $table->dropColumn(['source', 'external_uid', 'slots']);
        });
    }
};
