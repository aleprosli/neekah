<?php

namespace App\Http\Controllers\Customer;

use App\Actions\SubmitReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Booking;
use Illuminate\Http\RedirectResponse;

class ReviewController extends Controller
{
    public function store(StoreReviewRequest $request, Booking $booking, SubmitReview $submitReview): RedirectResponse
    {
        $submitReview->handle($booking, $request->validated());

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Terima kasih! Review anda kini dipaparkan pada profil '.$booking->vendor->name.'.');
    }
}
