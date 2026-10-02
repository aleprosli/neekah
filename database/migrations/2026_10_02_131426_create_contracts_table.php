<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Pro vendor's contract with a client, signed online by its token. Once
     * sent it is never edited; what was signed is kept with a hash of exactly
     * what the client read, and the drawn signature sits on the private disk.
     */
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 20);
            $table->string('token', 20)->unique();
            $table->string('status', 20)->default('draft');
            $table->string('client_name');
            $table->string('client_phone', 30)->nullable();
            $table->string('client_email')->nullable();
            $table->date('event_date')->nullable();
            $table->json('sections');
            $table->string('vendor_signatory')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->string('signer_name')->nullable();
            $table->string('signer_ip', 45)->nullable();
            $table->string('signer_user_agent', 500)->nullable();
            $table->string('signature_path')->nullable();
            $table->string('content_hash', 64)->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->string('void_reason', 500)->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'number']);
            $table->index(['vendor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
