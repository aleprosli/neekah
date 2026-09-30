<?php

namespace App\Console\Commands;

use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Support\ContentVersion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateVendorPopularity extends Command
{
    protected $signature = 'neekah:vendor-popularity';

    protected $description = 'Count each vendor\'s profile views over the last 30 days and mark the most viewed in each category as trending';

    /** Days of profile views behind the "Paling ramai dilihat" order. */
    public const DAYS = 30;

    /** Most viewed vendors per category that carry the Trending badge. */
    public const TRENDING_PER_CATEGORY = 3;

    /** Fewest views in the window before a vendor can be trending at all. */
    public const TRENDING_MIN_VIEWS = 20;

    /**
     * Popularity is kept beside the score, never in it: it orders one sort
     * and draws one badge. Written straight to the table so no vendor's
     * updated_at (and the sitemap's lastmod) moves, then the listing cache
     * is told once.
     */
    public function handle(): int
    {
        $views = DB::table('vendor_daily_stats')
            ->where('date', '>=', today()->subDays(self::DAYS - 1)->toDateString())
            ->groupBy('vendor_id')
            ->pluck(DB::raw('sum(profile_views)'), 'vendor_id')
            ->map(fn ($total): int => (int) $total);

        DB::transaction(function () use ($views): void {
            Vendor::query()->where('views_30d', '>', 0)->toBase()->update(['views_30d' => 0]);

            $views->filter()->each(fn (int $total, int $vendorId) => Vendor::query()->whereKey($vendorId)->toBase()->update(['views_30d' => $total]));
        });

        $trending = Vendor::query()
            ->where('status', VendorStatus::Approved)
            ->where('views_30d', '>=', self::TRENDING_MIN_VIEWS)
            ->orderByDesc('views_30d')
            ->orderBy('id')
            ->get(['id', 'category_id', 'trending_at'])
            ->groupBy('category_id')
            ->flatMap(fn ($vendors) => $vendors->take(self::TRENDING_PER_CATEGORY));

        // A vendor who stays trending keeps the day they first became so.
        Vendor::query()->whereNotNull('trending_at')->whereKeyNot($trending->pluck('id'))->toBase()->update(['trending_at' => null]);
        Vendor::query()->whereKey($trending->whereNull('trending_at')->pluck('id'))->toBase()->update(['trending_at' => now()]);

        ContentVersion::bumpGlobal();

        $this->components->info("{$views->count()} vendor(s) counted, {$trending->count()} trending.");

        return self::SUCCESS;
    }
}
