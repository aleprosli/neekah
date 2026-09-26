<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * A filter of resources/js/components/ui/DataTable.vue (UiFacetedFilter): its
 * options and counts, and reading the values the table asks for.
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
    public static function fromEnum(string $key, array $cases, array|string|null $value, ?Collection $counts = null, ?string $label = null): array
    {
        return [
            'key' => $key,
            'label' => $label,
            'value' => array_values(array_filter((array) $value, fn (mixed $one): bool => filled($one))),
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

    /**
     * The values asked for under $key, as the table sends them ("a,b") or as
     * an array (key[]=a), kept to those in $allowed. An empty list means no
     * filter.
     *
     * @param  list<string>  $allowed
     * @return list<string>
     */
    public static function requested(Request $request, string $key, array $allowed): array
    {
        $raw = $request->query($key, $request->input($key));
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        return array_values(array_unique(array_intersect(array_map(fn (mixed $value): string => trim((string) $value), $values), $allowed)));
    }

    /**
     * The enum cases asked for under $key.
     *
     * @template T of \BackedEnum
     *
     * @param  class-string<T>  $enum
     * @return list<T>
     */
    public static function requestedEnums(Request $request, string $key, string $enum): array
    {
        return array_map(
            fn (string $value): \BackedEnum => $enum::from($value),
            self::requested($request, $key, array_map(fn (\BackedEnum $case): string => (string) $case->value, $enum::cases())),
        );
    }
}
