<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_sites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('subdomain', 63)->unique();
            $table->string('template', 40)->default('klasik');
            $table->boolean('is_published')->default(false);

            $table->string('salutation')->nullable();
            $table->string('bride_name');
            $table->string('groom_name');
            $table->string('bride_parents')->nullable();
            $table->string('groom_parents')->nullable();
            $table->text('invitation_note')->nullable();

            $table->date('event_date');
            $table->time('starts_at')->nullable();
            $table->time('ends_at')->nullable();
            $table->string('venue_name')->nullable();
            $table->text('venue_address')->nullable();
            $table->string('map_url')->nullable();

            $table->json('itinerary')->nullable();
            $table->json('contacts')->nullable();
            $table->string('cover_image')->nullable();

            $table->boolean('rsvp_enabled')->default(true);
            $table->date('rsvp_deadline')->nullable();
            $table->text('closing_note')->nullable();
            $table->unsignedInteger('views')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_sites');
    }
};
