<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payments.status was varchar(20), and "awaiting_verification" is 21
 * characters: MySQL refused every payment a couple recorded. SQLite, which the
 * suite runs on, ignores the length, so nothing caught it until the demo
 * seeder ran against MySQL on the staging server.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 32)->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('status', 20)->default('pending')->change();
        });
    }
};
