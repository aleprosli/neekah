<?php

namespace App\Support;

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
            'allLabel' => $counts ? 'Semua ('.$counts->sum().')' : 'Semua',
            'options' => array_map(fn (\BackedEnum $case): array => [
                'value' => $case->value,
                'label' => method_exists($case, 'label') ? $case->label() : $case->name,
                'count' => $counts?->get($case->value, 0),
            ], $cases),
        ];
    }
}
