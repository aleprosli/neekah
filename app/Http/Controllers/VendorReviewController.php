<?php

namespace App\Http\Controllers;

use App\Actions\SubmitVendorReview;
use App\Http\Requests\StoreVendorReviewRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;

class VendorReviewController extends Controller
{
    /**
     * A review written straight on the profile, by anyone.
     *
     * It appears immediately — nobody waits for approval to be heard. What
     * keeps it honest is on the other side: it is badged as unverified, it is
     * kept out of the rating and the ranking, and an admin can take it down.
     */
    public function store(StoreVendorReviewRequest $request, Vendor $vendor, SubmitVendorReview $submitReview): RedirectResponse
    {
        abort_unless($vendor->isApproved(), 404);

        // A vendor talking up their own profile is the one case worth refusing
        // outright, because it is the only one we can detect for certain.
        if ($request->user()?->id === $vendor->user_id) {
            return back()->withErrors(['comment' => __('flash.couple.own_profile_review')], 'review')->withInput();
        }

        $submitReview->handle(
            $vendor,
            $request->safe()->only(['rating', 'comment', 'author_name', 'author_email']),
            $request->file('photos') ?? [],
            $request->user(),
        );

        return redirect()
            ->route('vendors.show', $vendor)
            ->withFragment('review')
            // Its own key: the page already shows session('status') at the top,
            // and the visitor is being sent back down to the review section.
            ->with('reviewStatus', __('props.couple.review_dipaparkan', ['vendor' => $vendor->name]));
    }
}
