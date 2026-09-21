<?php

namespace App\Casts;

use App\Support\Locales;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * A column that holds one value per language.
 *
 * Admins add categories and checklist tasks themselves, so these cannot live
 * in a translation file: a row added tomorrow would have nowhere to be
 * translated. The column holds {"ms": "...", "en": "..."} and reading it gives
 * the language being served.
 *
 * Reading never returns null when anything was written. A category whose
 * English name nobody has filled in yet shows its Malay one — a blank name in
 * a dropdown tells a couple nothing, and an admin who has not got to it yet
 * should not take the page down with them.
 *
 * @implements CastsAttributes<string|null, array<string, string|null>|string|null>
 */
class Translatable implements CastsAttributes
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        $values = self::decode($value);

        if ($values === []) {
            return null;
        }

        return $values[Locales::current()]
            ?? $values[Locales::DEFAULT]
            ?? collect($values)->filter()->first();
    }

    /**
     * Every language at once, for an admin form that edits them side by side.
     *
     * @return array<string, string|null>
     */
    public static function all(Model $model, string $key): array
    {
        $values = self::decode($model->getRawOriginal($key) ?? $model->getAttributes()[$key] ?? null);

        return collect(Locales::codes())
            ->mapWithKeys(fn (string $code): array => [$code => $values[$code] ?? null])
            ->all();
    }

    /**
     * A string sets the default language and leaves the others alone, so code
     * and factories written before the column had languages keep working.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        $existing = self::decode($attributes[$key] ?? null);

        $incoming = is_array($value)
            ? collect($value)->only(Locales::codes())->all()
            : [Locales::DEFAULT => (string) $value];

        $merged = collect([...$existing, ...$incoming])
            ->map(fn (mixed $text): ?string => is_string($text) && trim($text) !== '' ? trim($text) : null)
            ->filter()
            ->all();

        return $merged === [] ? null : (string) json_encode($merged, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, string>
     */
    private static function decode(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        // A value written before the column held languages is a plain string.
        return is_array($decoded) ? $decoded : [Locales::DEFAULT => $value];
    }
}
