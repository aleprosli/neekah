<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The text an admin writes, in every language the site is served in.
     *
     * These rows cannot be translated in a language file: admins add their own
     * categories and checklist tasks, and a row added tomorrow would have
     * nowhere to go. Each column becomes {"ms": "...", "en": "..."} instead,
     * read through the Translatable cast.
     *
     * site_templates.style stays a plain string: it is a closed set of five and
     * it is in the gallery's URLs, so translating it would move those URLs.
     *
     * @var array<string, array<int, string>>
     */
    private const COLUMNS = [
        'categories' => ['name', 'examples'],
        'checklist_sections' => ['title', 'note'],
        'checklist_items' => ['group', 'title', 'notes'],
        'site_templates' => ['name', 'description'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            // Everything written so far is Malay.
            foreach ($columns as $column) {
                DB::table($table)->whereNotNull($column)->orderBy('id')->chunkById(200, function ($rows) use ($table, $column): void {
                    foreach ($rows as $row) {
                        DB::table($table)->where('id', $row->id)->update([
                            $column => json_encode(['ms' => $row->{$column}], JSON_UNESCAPED_UNICODE),
                        ]);
                    }
                });
            }

            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->json($column)->nullable()->change();
                }
            });
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            Schema::table($table, function (Blueprint $blueprint) use ($columns): void {
                foreach ($columns as $column) {
                    $blueprint->text($column)->nullable()->change();
                }
            });

            foreach ($columns as $column) {
                DB::table($table)->whereNotNull($column)->orderBy('id')->chunkById(200, function ($rows) use ($table, $column): void {
                    foreach ($rows as $row) {
                        $decoded = json_decode((string) $row->{$column}, true);

                        DB::table($table)->where('id', $row->id)->update([
                            $column => is_array($decoded) ? ($decoded['ms'] ?? reset($decoded) ?: null) : $row->{$column},
                        ]);
                    }
                });
            }
        }
    }
};
