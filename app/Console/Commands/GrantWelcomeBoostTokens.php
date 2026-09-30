<?php

namespace App\Console\Commands;

use App\Actions\GrantBoostTokens;
use App\Enums\BoostTokenReason;
use App\Enums\VendorStatus;
use App\Models\Vendor;
use App\Notifications\BoostTokensReceived;
use App\Support\BoostSettings;
use Illuminate\Console\Command;

class GrantWelcomeBoostTokens extends Command
{
    protected $signature = 'neekah:boost-welcome
        {--silent-notify : Give the tokens without emailing the vendors}
        {--dry-run : List the vendors who would get the tokens, without giving or emailing anything}';

    protected $description = 'Give the welcome boost tokens once to every approved vendor who has not had them (vendors approved before boosting existed)';

    public function handle(GrantBoostTokens $grant, BoostSettings $settings): int
    {
        $tokens = $settings->welcomeTokens();
        $given = 0;

        if ($this->option('dry-run')) {
            $owed = Vendor::query()->where('status', VendorStatus::Approved)->get()
                ->filter(fn (Vendor $vendor): bool => $grant->isOwedWelcome($vendor, $tokens));

            $this->table(['ID', 'Vendor', 'Balance'], $owed->map(fn (Vendor $vendor): array => [$vendor->id, $vendor->name, $vendor->boost_tokens])->all());
            $this->components->info("{$owed->count()} vendor(s) would be given {$tokens} welcome token(s). Nothing was changed.");

            return self::SUCCESS;
        }

        Vendor::query()->where('status', VendorStatus::Approved)->with('user')->each(function (Vendor $vendor) use ($grant, $tokens, &$given): void {
            if ($grant->welcome($vendor, $tokens)) {
                if (! $this->option('silent-notify')) {
                    $vendor->user->notify(new BoostTokensReceived($tokens, BoostTokenReason::Welcome, $vendor->boost_tokens));
                }
                $given++;
            }
        });

        $this->components->info("{$given} vendor(s) given {$tokens} welcome token(s).");

        return self::SUCCESS;
    }
}
