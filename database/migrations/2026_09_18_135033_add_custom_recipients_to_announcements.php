<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            // Addresses typed in by hand, which may belong to nobody with an account.
            $table->json('custom_emails')->nullable()->after('body');
        });

        Schema::create('announcement_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['announcement_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('announcement_user');

        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropColumn('custom_emails');
        });
    }
};
