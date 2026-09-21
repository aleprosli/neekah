<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Collection;

/**
 * A chip filter for resources/js/components/ui/DataTable.vue.
 *
 * The chips used to be links that reloaded the page with the filter in the
 * table's own endpoint url, where it collided with paging. They belong to the
 * table instead, which swaps its rows in place.
 */
class TableFilter
{
    /**
     * How many rows sit behind each value of a column, plus '' for the lot.
     *
     * The chips count what the OTHER filters and the search already narrowed
     * the list to, so "Setup lengkap 60" can never sit above an empty table.
     *
     * @param  Builder<*>|Relation<*, *, *>  $query
     * @return array<string, int>
     */
    public static function countsByColumn(Builder|Relation $query, string $column): array
    {
        $counts = $query->clone()
            ->reorder()
            ->selectRaw($column.' as value, count(*) as total')
            ->groupBy($column)
            ->pluck('total', 'value')
            ->map(fn ($total): int => (int) $total)
            ->all();

        return ['' => array_sum($counts)] + $counts;
    }

    /**
     * A group built from an enum's cases, each chip carrying how many rows it holds.
     *
     * @param  array<int, \BackedEnum>  $cases
     * @param  Collection<string, int>|null  $counts  keyed by the case value
     * @return array<string, mixed>
     */
    public static function fromEnum(string $key, array $cases, ?string $value, ?Collection $counts = null, ?string $label = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            // The count rides on the chip as a badge, kept up to date by the
            // table from what the endpoint counted.
            'allLabel' => __('props.common.all'),
            'allCount' => $counts?->sum(),
            'options' => array_map(fn (\BackedEnum $case): array => [
                'value' => $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : $case->name,
                'count' => $counts?->get($case->value, 0),
            ], $cases),
        ];
    }
}
