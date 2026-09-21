<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The language to write to this person in.
     *
     * A page's language is its URL, so a link means the same thing to whoever
     * opens it. An email has no URL and nobody to open it but one person, so
     * it follows the language they were last reading the site in. Null until
     * they have been seen, and the default is used.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('locale', 5)->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('locale');
        });
    }
};
