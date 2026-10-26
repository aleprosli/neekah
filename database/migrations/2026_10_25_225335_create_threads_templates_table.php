<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The bank the Threads planner writes from: captions for vendor listings and
 * blog shares (with placeholders), and ready questions, polls, tips and promos.
 * The least recently used template of a type is picked next, so the account
 * does not say the same thing twice in a row.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threads_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20)->index();
            $table->text('body');
            $table->json('poll_options')->nullable();
            $table->string('topic_tag', 50)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads_templates');
    }
};
