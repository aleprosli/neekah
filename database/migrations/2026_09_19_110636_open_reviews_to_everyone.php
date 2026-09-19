<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reviews used to be the closing step of a booking: one per booking, always by
 * a signed-in customer, with all five aspects filled in. They are now open to
 * anyone, so every one of those assumptions has to become optional — while the
 * booking ones stay exactly what they were, because they are the only reviews
 * the rating and the ranking are still allowed to read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            // A review with no booking behind it, and one written by someone
            // who never signed in. The unique index on booking_id survives:
            // it still forbids two reviews of one booking, and allows any
            // number of rows that have no booking at all.
            $table->foreignId('booking_id')->nullable()->change();
            $table->foreignId('user_id')->nullable()->change();

            // Who wrote it, when there is no account to ask.
            $table->string('author_name')->nullable()->after('user_id');
            // Never shown publicly; it is how an admin reaches the author
            // about a review someone has disputed.
            $table->string('author_email')->nullable()->after('author_name');

            foreach (['quality', 'service', 'communication', 'value', 'punctuality'] as $aspect) {
                $table->unsignedTinyInteger($aspect)->nullable()->change();
            }

            // Hidden, not deleted: an admin taking a review down leaves the
            // row, its reason and its author behind, because "it was removed"
            // is itself a thing the next admin needs to be able to read.
            $table->timestamp('hidden_at')->nullable();
            $table->foreignId('hidden_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('hidden_reason')->nullable();

            // A vendor cannot take a review down, so this is how they object.
            $table->timestamp('reported_at')->nullable();
            $table->text('reported_reason')->nullable();

            // The vendor's public answer, shown under the review.
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();

            // Set when an admin typed the review in on someone else's behalf,
            // migrating one the vendor already had elsewhere.
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();

            $table->index(['vendor_id', 'hidden_at']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['vendor_id', 'hidden_at']);
            $table->dropConstrainedForeignId('hidden_by');
            $table->dropConstrainedForeignId('added_by');
            $table->dropColumn([
                'author_name', 'author_email', 'hidden_at', 'hidden_reason',
                'reported_at', 'reported_reason', 'reply', 'replied_at',
            ]);
        });
    }
};
