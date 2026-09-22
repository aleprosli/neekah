<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Admin-editable configuration, one row per dotted key. Read through the typed
 * classes in App\Support (ImageSettings, ...) rather than directly, so every
 * value has a default and a known shape.
 */
#[Fillable(['key', 'value'])]
class Setting extends Model
{
    private const CACHE_KEY = 'settings';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Every stored setting, read once and cached until the next save.
     *
     * Through memo(), so the twenty-odd callers in a single page render cost
     * one read of the underlying store rather than twenty. On the database
     * store each of those was a separate round trip to MySQL.
     *
     * @return array<string, mixed>
     */
    public static function values(): array
    {
        return Cache::memo()->rememberForever(self::CACHE_KEY, fn (): array => static::query()->pluck('value', 'key')->all());
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        // Through memo() as well, so the value this request already read is
        // dropped along with the one in the underlying store.
        Cache::memo()->forget(self::CACHE_KEY);
    }
}
