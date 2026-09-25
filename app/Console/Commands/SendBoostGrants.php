<?php

namespace App\Console\Commands;

use App\Actions\GrantBoostTokens;
use App\Enums\BoostTokenReason;
use App\Models\Vendor;
use App\Models\VendorBoost;
use App\Notifications\BoostEnding;
use App\Notifications\BoostTokensReceived;
use App\Support\BoostSettings;
use Illuminate\Console\Command;

class SendBoostGrants extends Command
{
    protected $signature = 'neekah:boost-grants';

    protected $description = 'Give Pro vendors their monthly boost tokens and remind vendors whose boost ends tomorrow';

    /** Days between two monthly grants to the same Pro vendor. */
    public const MONTH_DAYS = 30;

    public function handle(GrantBoostTokens $grant, BoostSettings $settings): int
    {
        $tokens = $settings->proMonthlyTokens();
        $granted = 0;

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

        // Matched on the calendar day, so a daily run reminds each boost once.
        $ending = 0;
        VendorBoost::query()
            ->whereDate('ends_at', today()->addDay())
            ->with(['vendor.user', 'category'])
            ->each(function (VendorBoost $boost) use (&$ending): void {
                $boost->vendor->user->notify(new BoostEnding($boost));
                $ending++;
            });

        $this->components->info("{$granted} Pro vendor(s) given tokens, {$ending} boost reminder(s) sent.");

        return self::SUCCESS;
    }
}
