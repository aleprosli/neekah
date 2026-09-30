<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every change to a vendor's boost tokens: given, bought, spent. The sum
     * is vendors.boost_tokens; GrantBoostTokens writes both together.
     */
    public function up(): void
    {
        Schema::create('vendor_boost_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vendor_id')->constrained()->cascadeOnDelete();
            $table->integer('change');
            $table->string('reason', 20);
            $table->nullableMorphs('source');
            $table->string('note')->nullable();
            $table->foreignId('added_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vendor_id', 'reason', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_boost_entries');
    }
};
