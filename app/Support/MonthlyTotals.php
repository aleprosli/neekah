<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

class MonthlyTotals
{
    /**
     * Group a query by calendar month on a date column, keyed "Y-m".
     *
     * Written with a raw date format rather than a driver-specific helper so
     * the same query works on the SQLite used in tests and the MySQL used in
     * production.
     *
     * @param  Builder<covariant \Illuminate\Database\Eloquent\Model>|Relation<covariant \Illuminate\Database\Eloquent\Model, covariant \Illuminate\Database\Eloquent\Model, *>  $query
     * @return Collection<string, float>
     */
    public static function of(Builder|Relation $query, string $column, string $aggregate = 'count', string $valueColumn = '*'): Collection
    {
        $base = $query instanceof Relation ? $query->getQuery()->getQuery() : $query->getQuery();

        $driver = $base->getConnection()->getDriverName();
        $month = $driver === 'sqlite'
            ? "strftime('%Y-%m', {$column})"
            : "DATE_FORMAT({$column}, '%Y-%m')";

        $value = $aggregate === 'count' ? 'count(*)' : "{$aggregate}({$valueColumn})";

        return $base
            ->selectRaw("{$month} as period, {$value} as total")
            ->groupBy('period')
            ->pluck('total', 'period')
            ->map(fn ($total): float => (float) $total);
    }
}
