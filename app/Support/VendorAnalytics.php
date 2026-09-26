<?php

namespace App\Support;

use App\Models\Vendor;
use App\Models\VendorDailyStat;
use Illuminate\Support\Collection;

/**
 * What a vendor's page is doing for them: views, and taps on WhatsApp and the
 * phone number, per day.
 *
 * Every vendor is counted; what they are shown depends on Pro. A free vendor
 * sees last week's totals, a Pro one the whole window and the trend.
 */
class VendorAnalytics
{
    /** Four weeks: long enough to see a trend, short enough to act on. */
    public const DAYS = 28;

    /** The teaser a free vendor sees. */
    public const TEASER_DAYS = 7;

    public function __construct(private Vendor $vendor) {}

    /**
     * Totals over the last $days days, today included.
     *
     * @return array{profile_views: int, whatsapp_clicks: int, phone_clicks: int}
     */
    public function totals(int $days): array
    {
        $rows = $this->rows($days);

        return collect(VendorDailyStat::COUNTERS)
            ->mapWithKeys(fn (string $counter): array => [$counter => (int) $rows->sum($counter)])
            ->all();
    }

    /**
     * Every day of the window, including the quiet ones: a chart that skips the
     * days nobody looked reads as steady interest when it was not.
     *
     * @return array<int, array{date: string, label: string, value: int}>
     */
    public function dailyViews(int $days = self::DAYS): array
    {
        $rows = $this->rows($days)->keyBy(fn (VendorDailyStat $row): string => $row->date->toDateString());

        return collect(range($days - 1, 0))
            ->map(function (int $back) use ($rows): array {
                $day = today()->subDays($back);

                return [
                    'date' => $day->toDateString(),
                    'label' => $day->translatedFormat('j M'),
                    'value' => (int) ($rows->get($day->toDateString())?->profile_views ?? 0),
                ];
            })
            ->all();
    }

    /**
     * @return Collection<int, VendorDailyStat>
     */
    private function rows(int $days): Collection
    {
        return $this->vendor->dailyStats()
            ->where('date', '>=', today()->subDays($days - 1)->toDateString())
            ->get();
    }
}
