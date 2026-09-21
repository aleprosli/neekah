<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\ModerateReview;
use App\Actions\SubmitVendorReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorAddedReviewRequest;
use App\Models\Review;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * What a vendor may do about a review: answer it in public, or ask an admin to
 * look at it. Not remove it — see App\Policies\ReviewPolicy for why.
 */
class ReviewController extends Controller
{
    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        abort_unless((bool) $vendor, 404);

        $reviews = $vendor->reviews()
            ->with(['user', 'photos'])
            ->latest()
            ->paginate(10);

        return view('vendor.reviews.index', [
            'vendor' => $vendor,
            'reviews' => $reviews,
        ]);
    }

    /**
     * A review the vendor already had elsewhere — Google, Instagram, a message
     * from a couple — carried onto their Neekah profile.
     *
     * Stored as an open review, so it moves no rating, no points and no tier,
     * and stamped with the vendor's own account so the public page can say who
     * put it there. An admin can still take it down like any other.
     */
    public function store(StoreVendorAddedReviewRequest $request, SubmitVendorReview $submitReview): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        $review = $submitReview->handle(
            $vendor,
            $request->safe()->only(['rating', 'comment', 'author_name', 'author_email']),
            $request->file('photos') ?? [],
            addedBy: $request->user(),
        );

        if ($writtenOn = $request->date('written_on')) {
            $review->forceFill(['created_at' => $writtenOn])->save();
        }

        return back()->with('status', __('pages.reviews.review_ditambah', ['author' => $review->author_name]));
    }

    /**
     * Only ever a review this vendor typed in themselves — for a typo, or a
     * testimonial the writer later asked them to take down.
     */
    public function destroy(Review $review, ModerateReview $moderate): RedirectResponse
    {
        Gate::authorize('deleteOwnAddition', $review);

        $author = $review->author_name;
        $moderate->delete($review);

        return back()->with('status', 'Review oleh '.$author.' dipadam.');
    }

    public function reply(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('reply', $review);

        $validated = $request->validate([
            'reply' => ['required', 'string', 'min:5', 'max:1000'],
        ], attributes: ['reply' => 'jawapan']);

        $review->forceFill([
            'reply' => $validated['reply'],
            'replied_at' => now(),
        ])->save();

        return back()->with('status', 'Jawapan anda dipaparkan di bawah review itu.');
    }

    public function report(Request $request, Review $review): RedirectResponse
    {
        Gate::authorize('report', $review);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:10', 'max:1000'],
        ], attributes: ['reason' => 'sebab']);

        $review->forceFill([
            'reported_at' => now(),
            'reported_reason' => $validated['reason'],
        ])->save();

        return back()->with('status', 'Laporan anda dihantar. Review ini kekal dipaparkan sehingga admin memeriksanya.');
    }
}
