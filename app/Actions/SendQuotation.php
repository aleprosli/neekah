<?php

namespace App\Actions;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Notifications\QuotationSent;
use Illuminate\Support\Facades\Notification;

/**
 * Open a quotation to the client. Most vendors pass the link on WhatsApp
 * themselves; when the client has an email, it goes there as well. Sending
 * again re-sends the email and keeps everything else.
 */
class SendQuotation
{
    public function handle(Quotation $quotation, bool $email = true): Quotation
    {
        if ($quotation->status === QuotationStatus::Draft) {
            $quotation->update(['status' => QuotationStatus::Sent, 'sent_at' => now()]);
        }

        if ($email && filled($quotation->client_email)) {
            Notification::route('mail', $quotation->client_email)->notify(new QuotationSent($quotation));
        }

        return $quotation;
    }
}
