<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every stored instant, moved from UTC to Kuala Lumpur.
     *
     * The application read and wrote UTC while the server clock and the nginx
     * log ran on +08, so the three never agreed and every timestamp shown to a
     * person was eight hours behind — a photo uploaded at 2:27am on the 22nd
     * read "6:27 PM" on the 21st. The application now runs in Kuala Lumpur, so
     * the values already stored have to move with it.
     *
     * Only timestamp columns are touched. A date column is a calendar date —
     * a wedding on the 14th is on the 14th wherever you read it from — and a
     * time column is the hour on a programme. Shifting either would move a
     * wedding, so the type is the whole test of what belongs here.
     */
    public function up(): void
    {
        $this->shift($this->hours());
    }

    public function down(): void
    {
        $this->shift(-$this->hours());
    }

    /**
     * How far Kuala Lumpur is ahead of the UTC the rows were written in.
     * Malaysia has no daylight saving, so this is +8 at any instant.
     */
    private function hours(): int
    {
        return (int) ((new DateTimeZone('Asia/Kuala_Lumpur'))
            ->getOffset(new DateTime('now', new DateTimeZone('UTC'))) / 3600);
    }

    private function shift(int $hours): void
    {
        foreach ($this->timestampColumns() as [$table, $column]) {
            DB::table($table)
                ->whereNotNull($column)
                ->update([$column => $this->expression($column, $hours)]);
        }
    }

    /**
     * Read from the schema rather than a list written by hand, so a column
     * added since cannot be left behind in the old timezone.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    private function timestampColumns(): array
    {
        $found = [];

        foreach ($this->tables() as $table) {
            $name = $table['name'];

            foreach (Schema::getColumns($name) as $column) {
                // SQLite calls them datetime, MySQL timestamp; date and time
                // are deliberately not in this list.
                if (in_array($column['type_name'], ['timestamp', 'datetime'], true)) {
                    $found[$name.'.'.$column['name']] = [$name, $column['name']];
                }
            }
        }

        // Keyed, so a table that somehow arrives twice still shifts once. A
        // second pass would move every row another eight hours.
        return array_values($found);
    }

    /**
     * The tables of this database, and no other.
     *
     * Schema::getTables() with no argument reaches every schema the connection
     * can see. On a MySQL server holding more than one database that returns
     * the same table name once per schema — 399 columns where there are 87 —
     * and the rows would be shifted once per appearance. SQLite has a single
     * schema and does not take the name.
     *
     * @return array<int, array<string, mixed>>
     */
    private function tables(): array
    {
        return DB::getDriverName() === 'sqlite'
            ? Schema::getTables()
            : Schema::getTables(DB::getDatabaseName());
    }

    private function expression(string $column, int $hours): Expression
    {
        $quoted = DB::getQueryGrammar()->wrap($column);

        return DB::raw(match (DB::getDriverName()) {
            'sqlite' => "datetime({$quoted}, '{$hours} hours')",
            'pgsql' => "{$quoted} + interval '{$hours} hours'",
            default => "{$quoted} + INTERVAL {$hours} HOUR",
        });
    }
};
