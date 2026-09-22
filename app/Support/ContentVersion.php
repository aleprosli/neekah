<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * The string every cached public payload carries in its key.
 *
 * A write bumps the version, so the old entry is simply never asked for again.
 * Nothing is deleted and nothing has to be cleared by hand, which is the same
 * idea as the "<count>-<max updated_at>" key the card gallery uses. The
 * difference is that this is written when a model is saved rather than
 * measured on every read: measuring it costs a query per cache, and on the
 * pages here that was most of what the cache was saving.
 *
 * Two scopes, because one is too coarse. GLOBAL moves when anything a listing
 * shows changes - a vendor approved, edited or rescored, a category hidden, an
 * admin setting saved. A vendor's own scope moves when that vendor's packages,
 * portfolio or reviews change, so one vendor editing one price does not throw
 * away the other three hundred profiles.
 *
 * Read through memo(), like Setting::values(), because CACHE_STORE is database
 * in production and both scopes are asked for more than once per render.
 */
class ContentVersion
{
    /**
     * How long a payload keyed by one of these versions may live.
     *
     * The version is what actually keeps the pages honest; this is the backstop
     * for what it cannot see, such as a review photo removed without its review
     * being saved, or a vendor row changed by a query-builder update that fires
     * no model event.
     */
    public const TTL = 3600;

    private const GLOBAL_KEY = 'content-version:global';

    /**
     * Moves whenever anything a listing renders changes.
     */
    public static function global(): string
    {
        return self::read(self::GLOBAL_KEY);
    }

    /**
     * Moves whenever this vendor's own catalogue, portfolio or reviews change.
     */
    public static function forVendor(int|string $vendorId): string
    {
        return self::read(self::vendorKey($vendorId));
    }

    public static function bumpGlobal(): void
    {
        self::bump(self::GLOBAL_KEY);
    }

    public static function bumpVendor(int|string|null $vendorId): void
    {
        if (blank($vendorId)) {
            return;
        }

        self::bump(self::vendorKey($vendorId));
    }

    /**
     * An unset version is written rather than treated as "no cache", so the
     * first visitor after a deploy warms it instead of every visitor paying to
     * discover it is missing.
     */
    private static function read(string $key): string
    {
        return Cache::memo()->rememberForever($key, fn (): string => self::mint());
    }

    private static function bump(string $key): void
    {
        // forget() first, write second, and never the other way round:
        // memo()'s forget clears the underlying store as well as the memo, so
        // forgetting after the write deletes the version that was just minted
        // and leaves the next reader to invent another one.
        Cache::memo()->forget($key);

        Cache::forever($key, self::mint());
    }

    private static function mint(): string
    {
        return Str::random(12);
    }

    private static function vendorKey(int|string $vendorId): string
    {
        return 'content-version:vendor:'.$vendorId;
    }
}
