<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every post on Neekah's Threads account, from the moment it is planned to
 * the moment it is live: what it says, when it goes, and what Threads
 * answered. A cancelled post keeps its row so its slot stays decided.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('threads_posts', function (Blueprint $table) {
            $table->id();
            $table->string('type', 20);
            $table->string('status', 20)->default('scheduled');
            $table->text('text');
            $table->string('image_path')->nullable();
            $table->string('image_url', 2048)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->string('topic_tag', 50)->nullable();
            $table->json('poll_options')->nullable();
            $table->foreignId('vendor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('threads_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('scheduled_at');
            // The daily slot the planner filled, kept when an admin moves the
            // time, so the planner does not fill that slot a second time.
            $table->dateTime('slot_at')->nullable()->unique();
            $table->string('container_id')->nullable();
            $table->timestamp('container_created_at')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('threads_media_id')->nullable();
            $table->string('permalink', 2048)->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index('scheduled_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('threads_posts');
    }
};
