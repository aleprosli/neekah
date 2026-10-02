<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A Pro vendor's quotation: a snapshot of packages and add-ons, priced
     * once on save, which the client opens by its token without an account.
     * Once accepted it can be issued again as an invoice, under its own
     * running number.
     */
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('enquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('booking_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 20);
            $table->string('token', 20)->unique();
            $table->string('status', 20)->default('draft');
            $table->string('client_name');
            $table->string('client_phone', 30)->nullable();
            $table->string('client_email')->nullable();
            $table->date('event_date')->nullable();
            $table->string('event_location')->nullable();
            $table->date('valid_until');
            $table->string('discount_type', 10)->default('fixed');
            $table->decimal('discount_value', 10, 2)->default(0);
            $table->string('deposit_type', 10)->default('percent');
            $table->decimal('deposit_value', 10, 2)->default(0);
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('viewed_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->string('accepted_name')->nullable();
            $table->string('accepted_ip', 45)->nullable();
            $table->timestamp('declined_at')->nullable();
            $table->string('decline_reason', 500)->nullable();
            $table->string('invoice_number', 20)->nullable();
            $table->timestamp('invoiced_at')->nullable();
            $table->string('invoice_status', 20)->nullable();
            $table->timestamps();

            $table->unique(['vendor_id', 'number']);
            $table->index(['vendor_id', 'status']);
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('features')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 10, 2);
            $table->decimal('line_total', 10, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};
