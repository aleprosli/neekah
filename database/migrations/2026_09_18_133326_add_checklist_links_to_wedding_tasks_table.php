<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_tasks', function (Blueprint $table): void {
            $table->foreignId('checklist_item_id')->nullable()->after('category_id')->constrained()->nullOnDelete();
            $table->foreignId('checklist_section_id')->nullable()->after('checklist_item_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('wedding_tasks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('checklist_item_id');
            $table->dropConstrainedForeignId('checklist_section_id');
        });
    }
};
