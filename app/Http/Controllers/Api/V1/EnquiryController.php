<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\ReplyToEnquiry;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReplyEnquiryRequest;
use App\Http\Resources\Api\V1\EnquiryDetailResource;
use App\Http\Resources\Api\V1\EnquiryResource;
use App\Models\Enquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/**
 * Couples asking the vendor about a date or a package, and the replies.
 */
class EnquiryController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $status = EnquiryStatus::tryFrom($request->string('status')->toString());
        $enquiries = $request->user()->vendor->enquiries();

        return EnquiryResource::collection($enquiries->clone()
            ->with(['user', 'package'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->latest()
            ->orderByDesc('id')
            ->paginate(20))
            ->additional(['counts' => [
                'open' => $enquiries->clone()->where('status', EnquiryStatus::Open)->count(),
                'replied' => $enquiries->clone()->where('status', EnquiryStatus::Replied)->count(),
            ]]);
    }

    public function show(Enquiry $enquiry): EnquiryDetailResource
    {
        Gate::authorize('view', $enquiry);

        return new EnquiryDetailResource($enquiry->load(['user', 'package', 'wedding']));
    }

    public function reply(ReplyEnquiryRequest $request, Enquiry $enquiry, ReplyToEnquiry $reply): JsonResponse
    {
        $reply->handle($enquiry, $request->string('reply')->toString());

        return response()->json([
            'message' => __('flash.vendor.enquiry_replied'),
            'data' => new EnquiryDetailResource($enquiry->fresh(['user', 'package', 'wedding'])),
        ]);
    }
}
