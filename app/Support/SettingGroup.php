<?php

namespace App\Support;

use App\Models\Setting;

/**
 * One group of admin-editable settings, stored under a shared key prefix.
 *
 * Every value has a default, so a fresh install behaves sensibly before anyone
 * opens Admin → Tetapan, and a group can gain a key without a migration.
 */
abstract class SettingGroup
{
    /**
     * The value used when the admin has not saved one, keyed without the prefix.
     *
     * @return array<string, int|string|bool|null>
     */
    abstract public static function defaults(): array;

    /** The dotted prefix every key in this group is stored under. */
    abstract protected static function prefix(): string;

    /**
     * @return array<string, int|string|bool|null>
     */
    public function all(): array
    {
        $stored = Setting::values();

        return collect(static::defaults())
            ->map(fn (int|string|bool|null $default, string $key) => $stored[static::prefix().'.'.$key] ?? $default)
            ->all();
    }

    /**
     * Save the given keys; anything not passed keeps its current value.
     *
     * @param  array<string, int|string|bool|null>  $values
     */
    public function save(array $values): void
    {
        Setting::put(collect($values)
            ->only(array_keys(static::defaults()))
            ->mapWithKeys(fn (int|string|bool|null $value, string $key): array => [static::prefix().'.'.$key => $value])
            ->all());
    }

    protected function value(string $key): int|string|bool|null
    {
        return Setting::values()[static::prefix().'.'.$key] ?? static::defaults()[$key];
    }

    protected function string(string $key): string
    {
        return trim((string) $this->value($key));
    }

    /**
     * A value an admin writes once per language.
     *
     * Stored as the key itself for the default language and "key_en" for the
     * others, so a group gains a language without a migration and a value
     * written before any of this existed is still the default one.
     *
     * Falls back to the default language when the admin has not written the
     * other yet: better the Malay opening hours on an English page than a
     * blank line where the hours should be.
     */
    protected function localised(string $key): string
    {
        $locale = Locales::current();
        $forLocale = $key.'_'.$locale;

        if ($locale !== Locales::DEFAULT
            && array_key_exists($forLocale, static::defaults())
            && ($value = $this->string($forLocale)) !== '') {
            return $value;
        }

        return $this->string($key);
    }

    /**
     * The stored keys for a value that is written once per language, in the
     * order the languages are served. The admin form and the validation rules
     * are both built from this, so they cannot disagree.
     *
     * @return array<string, string> keyed by locale code
     */
    public static function localisedKeys(string $key): array
    {
        return collect(Locales::codes())
            ->mapWithKeys(fn (string $code): array => [
                $code => $code === Locales::DEFAULT ? $key : $key.'_'.$code,
            ])
            ->all();
    }
}
