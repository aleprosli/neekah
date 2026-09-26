<?php

namespace App\Actions;

use App\Enums\BoostTokenReason;
use App\Models\Payment;
use App\Notifications\BoostTokensReceived;
use Illuminate\Support\Facades\DB;

/**
 * What a paid boost pack buys: its tokens, credited to the vendor with the
 * payment as their source. SettlePayment calls it once, inside its lock.
 */
class ActivateBoostPurchase
{
    public function __construct(private GrantBoostTokens $tokens) {}

    public function fulfil(Payment $payment): void
    {
        $vendor = $payment->vendor;
        $tokens = (int) $payment->detail('tokens', 0);

        $this->tokens->handle($vendor, $tokens, BoostTokenReason::Purchase, $payment);

        DB::afterCommit(fn () => $vendor->user->notify(new BoostTokensReceived($tokens, BoostTokenReason::Purchase, (int) $vendor->fresh()->boost_tokens)));
    }
}
