<?php

namespace App\Models\Concerns;

use App\Support\Locales;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;

/**
 * Finding a row by text that is stored per language.
 *
 * where('name', 'Kereta Pengantin') stopped matching the moment the column
 * became {"ms": "Kereta Pengantin"}: the comparison is against the whole JSON
 * document. This looks inside it, in every language, so a caller can search by
 * whichever one they happen to know.
 */
trait HasTranslatedText
{
    #[Scope]
    protected function whereTranslated(Builder $query, string $column, string $value): Builder
    {
        return $query->where(function (Builder $query) use ($column, $value): void {
            foreach (Locales::codes() as $code) {
                $query->orWhere($column.'->'.$code, $value);
            }
        });
    }
}
