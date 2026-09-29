<?php

namespace App\Actions;

use App\Enums\EnquiryStatus;
use App\Enums\PointReason;
use App\Models\Enquiry;
use App\Notifications\EnquiryReplied;

/**
 * The vendor answering a couple, from the website or the Pro app. Replying
 * within a day is what the kertas kerja rewards, not the enquiry itself.
 */
class ReplyToEnquiry
{
    public function __construct(private AwardVendorPoints $awardPoints) {}

    public function handle(Enquiry $enquiry, string $reply): Enquiry
    {
        $firstReply = $enquiry->replied_at === null;

        $enquiry->update([
            'reply' => $reply,
            'replied_at' => now(),
            'status' => EnquiryStatus::Replied,
        ]);

        if ($firstReply && $enquiry->created_at->diffInHours(now()) < 24) {
            $this->awardPoints->award($enquiry->vendor, PointReason::FastResponse, $enquiry);
        }

        $enquiry->user->notify(new EnquiryReplied($enquiry->fresh(['vendor'])));

        return $enquiry;
    }
}
