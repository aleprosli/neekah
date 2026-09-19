<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
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
