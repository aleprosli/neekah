<?php

namespace App\Http\Controllers\Api\Partner;

use App\Actions\SubmitVendorReview;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePartnerReviewRequest;
use App\Http\Resources\Api\Partner\ReviewResource;
use App\Models\Vendor;
use Illuminate\Http\JsonResponse;

class VendorReviewController extends Controller
{
    /**
     * A review a partner's player wrote. It is an open review like one
     * written on the profile, published at once and kept out of the rating,
     * and stamped with the partner so the page says where it came from and
     * an admin can find them all.
     */
    public function store(StorePartnerReviewRequest $request, Vendor $vendor, SubmitVendorReview $submitReview): JsonResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $review = $submitReview->handle(
            $vendor,
            [
                ...$request->safe()->only(['rating', 'comment', 'author_name', 'author_email']),
                'source' => $request->attributes->get('partner'),
            ],
            $request->file('photos') ?? [],
        );

        $review->load('photos')->setRelation('vendor', $vendor);

        return (new ReviewResource($review))->response()->setStatusCode(201);
    }
}
