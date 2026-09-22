<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Daily counters behind the card's insights page.
 *
 * One row per card per day, not per visit: a couple wants to see the shape of the
 * week they sent the link, and a row per open would grow without ever being read
 * that way. Nothing identifying is kept here — who opened their own link is the
 * guest list's business (wedding_guests.opened_at), not this table's.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_site_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wedding_site_id')->constrained()->cascadeOnDelete();
            $table->date('viewed_on');
            $table->unsignedInteger('views')->default(0);
            // Opens that carried a guest's personal link, so the couple can tell
            // "the list is reading it" from "someone forwarded it".
            $table->unsignedInteger('guest_views')->default(0);
            $table->timestamps();

            $table->unique(['wedding_site_id', 'viewed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_site_views');
    }
};
