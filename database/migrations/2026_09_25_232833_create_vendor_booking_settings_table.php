<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A vendor's online booking rules and payment connection, kept off the
     * vendors row: every vendor save moves the marketplace cache version, and
     * a "my calendar is up to date" tap must not throw that cache away. The
     * Herepay keys are encrypted and never hydrated with the public payload.
     */
    public function up(): void
    {
        Schema::create('vendor_booking_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('enabled')->default(false);
            $table->string('deposit_type', 10)->default('percent');
            $table->decimal('deposit_value', 10, 2)->default(30);
            $table->unsignedTinyInteger('max_per_day')->default(1);
            $table->json('available_weekdays')->nullable();
            $table->unsignedSmallInteger('min_lead_days')->default(14);
            $table->unsignedTinyInteger('max_advance_months')->default(18);
            $table->text('deposit_terms')->nullable();
            $table->text('manual_instructions')->nullable();
            $table->text('herepay_secret_key')->nullable();
            $table->text('herepay_private_key')->nullable();
            $table->timestamp('herepay_connected_at')->nullable();
            $table->timestamp('herepay_verified_at')->nullable();
            $table->timestamp('calendar_confirmed_at')->nullable();
            $table->text('ical_url')->nullable();
            $table->timestamp('ical_synced_at')->nullable();
            $table->string('ical_error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_booking_settings');
    }
};
