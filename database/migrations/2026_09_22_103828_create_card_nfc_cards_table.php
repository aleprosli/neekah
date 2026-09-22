<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A physical NFC card or printed QR that opens one couple's invitation.
 *
 * The tag carries only the uid, never the address, so a card handed out before the
 * invitation exists still works, and a couple who changes their address does not
 * turn a stack of printed cards into rubbish.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('card_nfc_cards', function (Blueprint $table) {
            $table->id();
            $table->string('uid', 40)->unique();
            $table->foreignId('wedding_site_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('taps')->default(0);
            $table->timestamp('last_tapped_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('card_nfc_cards');
    }
};
