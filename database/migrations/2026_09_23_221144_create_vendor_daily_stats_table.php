<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily counters behind a vendor's analytics: one row per vendor per day, the
 * same shape as wedding_site_views. Nothing about who looked is kept.
 *
 * Kept for every vendor, not only Pro ones, so a vendor who upgrades sees the
 * weeks before they paid rather than an empty chart.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_daily_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('profile_views')->default(0);
            $table->unsignedInteger('whatsapp_clicks')->default(0);
            $table->unsignedInteger('phone_clicks')->default(0);
            $table->timestamps();

            $table->unique(['vendor_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_daily_stats');
    }
};
