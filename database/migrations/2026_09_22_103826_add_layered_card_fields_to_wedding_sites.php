<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What the layered designs ask a couple for, beyond what the old card had.
 *
 * The designed canvases print the names on their own line, each parent on their
 * own line and the couple's photos in shaped slots, so the single "Zulkifli bin
 * Hassan & Rohana binti Ahmad" line is split in two here rather than at render.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('wedding_sites', function (Blueprint $table) {
            // The short name the cover prints; "Aina", not "Nur Aina Zulkifli".
            $table->string('bride_short', 40)->nullable()->after('bride_name');
            $table->string('groom_short', 40)->nullable()->after('groom_name');
            $table->string('bride_father')->nullable()->after('bride_parents');
            $table->string('bride_mother')->nullable()->after('bride_father');
            $table->string('groom_father')->nullable()->after('groom_parents');
            $table->string('groom_mother')->nullable()->after('groom_father');
            $table->text('bride_bio')->nullable()->after('groom_mother');
            $table->text('groom_bio')->nullable()->after('bride_bio');

            // The couple's colour and type overrides, and the photo in each slot.
            $table->json('palette')->nullable()->after('template');
            $table->json('fonts')->nullable()->after('palette');
            $table->json('slot_images')->nullable()->after('fonts');
            // Which sections follow the designed canvases, in order.
            $table->json('widgets')->nullable()->after('slot_images');

            $table->foreignId('music_track_id')->nullable()->after('widgets')->constrained('card_music_tracks')->nullOnDelete();
            $table->boolean('music_enabled')->default(false)->after('music_track_id');
        });

        // The first name is what a cover prints.
        $short = function (?string $name): ?string {
            $first = preg_split('/\s+/', trim((string) $name))[0] ?? '';

            return $first === '' ? null : mb_substr($first, 0, 40);
        };

        // "Father & Mother" as the couple typed it, into the two columns designs print.
        $split = function (string $side, ?string $parents): array {
            $parts = preg_split('/\s*[&+]\s*|\s+dan\s+/iu', trim((string) $parents), 2) ?: [];

            return [
                $side.'_father' => ($parts[0] ?? '') === '' ? null : mb_substr($parts[0], 0, 255),
                $side.'_mother' => ($parts[1] ?? '') === '' ? null : mb_substr($parts[1], 0, 255),
            ];
        };

        foreach (DB::table('wedding_sites')->select('id', 'bride_name', 'groom_name', 'bride_parents', 'groom_parents')->get() as $site) {
            DB::table('wedding_sites')->where('id', $site->id)->update([
                'bride_short' => $short($site->bride_name),
                'groom_short' => $short($site->groom_name),
                ...$split('bride', $site->bride_parents),
                ...$split('groom', $site->groom_parents),
            ]);
        }

        Schema::table('wedding_sites', function (Blueprint $table) {
            $table->dropColumn(['bride_parents', 'groom_parents']);
        });
    }

    public function down(): void
    {
        Schema::table('wedding_sites', function (Blueprint $table) {
            $table->string('bride_parents')->nullable();
            $table->string('groom_parents')->nullable();
            $table->dropConstrainedForeignId('music_track_id');
            $table->dropColumn([
                'bride_short', 'groom_short', 'bride_father', 'bride_mother', 'groom_father', 'groom_mother',
                'bride_bio', 'groom_bio', 'palette', 'fonts', 'slot_images', 'widgets', 'music_enabled',
            ]);
        });
    }
};
