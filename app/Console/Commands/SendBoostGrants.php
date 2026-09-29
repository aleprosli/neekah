<?php

namespace App\Console\Commands;

use App\Actions\GrantBoostTokens;
use App\Enums\BoostTokenReason;
use App\Models\Vendor;
use App\Models\VendorBoost;
use App\Notifications\BoostEnding;
use App\Notifications\BoostTokensReceived;
use App\Support\BoostSettings;
use App\Support\ProSettings;
use Illuminate\Console\Command;

class SendBoostGrants extends Command
{
    protected $signature = 'neekah:boost-grants';

    protected $description = 'Give Pro vendors their monthly boost tokens, Elite vendors their bonus, and remind vendors whose boost ends tomorrow';

    /** Days between two monthly grants to the same Pro vendor. */
    public const MONTH_DAYS = 30;

    public function handle(GrantBoostTokens $grant, BoostSettings $settings, ProSettings $pro): int
    {
        $tokens = $settings->proMonthlyTokens();
        $granted = 0;
        $elite = 0;

        if ($tokens > 0) {
            Vendor::query()
                ->pro()
                ->whereDoesntHave('boostEntries', fn ($entries) => $entries
                    ->where('reason', BoostTokenReason::ProMonthly)
                    ->where('created_at', '>', now()->subDays(self::MONTH_DAYS)))
                ->with('user')
                ->each(function (Vendor $vendor) use ($grant, $tokens, &$granted): void {
                    $grant->handle($vendor, $tokens, BoostTokenReason::ProMonthly);
                    $vendor->user->notify(new BoostTokensReceived($tokens, BoostTokenReason::ProMonthly, $vendor->boost_tokens));
                    $granted++;
                });
        }

        // Pro Elite's bonus: its own ledger reason and its own 30 days, so a
        // vendor who becomes Elite mid-month gets it at once, and one who
        // drops out stops getting it.
        $bonus = $pro->eliteBonusTokens();

        if ($bonus > 0) {
            Vendor::query()
                ->elite()
                ->whereDoesntHave('boostEntries', fn ($entries) => $entries
                    ->where('reason', BoostTokenReason::EliteMonthly)
                    ->where('created_at', '>', now()->subDays(self::MONTH_DAYS)))
                ->with('user')
                ->each(function (Vendor $vendor) use ($grant, $bonus, &$elite): void {
                    $grant->handle($vendor, $bonus, BoostTokenReason::EliteMonthly);
                    $vendor->user->notify(new BoostTokensReceived($bonus, BoostTokenReason::EliteMonthly, $vendor->fresh()->boost_tokens));
                    $elite++;
                });
        }

        // Matched on the calendar day, so a daily run reminds each boost once.
        $ending = 0;
        VendorBoost::query()
            ->whereDate('ends_at', today()->addDay())
            ->with(['vendor.user', 'category'])
            ->each(function (VendorBoost $boost) use (&$ending): void {
                $boost->vendor->user->notify(new BoostEnding($boost));
                $ending++;
            });

        $this->components->info("{$granted} Pro vendor(s) given tokens, {$elite} Elite bonus(es), {$ending} boost reminder(s) sent.");

        return self::SUCCESS;
    }
}
