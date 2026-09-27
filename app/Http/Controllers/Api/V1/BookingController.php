<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\CancelBooking;
use App\Actions\CompleteBooking;
use App\Enums\BookingStatus;
use App\Enums\CancellationReason;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookingDetailResource;
use App\Http\Resources\Api\V1\BookingResource;
use App\Models\Booking;
use App\Support\TableFilter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/**
 * The vendor's bookings: the list, one booking, and completing or calling it
 * off — through the same actions as the website.
 */
class BookingController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $statuses = TableFilter::requestedEnums($request, 'status', BookingStatus::class);

        $matching = $request->user()->vendor->bookings()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('reference', 'like', $like)
                    ->orWhere('package_name', 'like', $like)
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $like)));
            });

        $bookings = $matching->clone()
            ->with(['user', 'payments'])
            ->when($statuses, fn ($query) => $query->whereIn('status', $statuses))
            // What is coming up first, then what has passed, most recent first.
            ->orderByRaw('case when event_date >= ? then 0 else 1 end', [today()->toDateString()])
            ->orderByRaw('case when event_date >= ? then event_date end asc', [today()->toDateString()])
            ->orderByDesc('event_date')
            ->paginate(min(max($request->integer('per_page', 20), 1), 50));

        return BookingResource::collection($bookings)->additional([
            'counts' => collect(BookingStatus::cases())
                ->mapWithKeys(fn (BookingStatus $status): array => [$status->value => 0])
                ->merge(collect(TableFilter::countsByColumn($matching, 'status'))->except(''))
                ->all(),
        ]);
    }

    public function show(Booking $booking): BookingDetailResource
    {
        Gate::authorize('view', $booking);

        return new BookingDetailResource(self::loaded($booking));
    }

    public function complete(Request $request, Booking $booking, CompleteBooking $completeBooking): JsonResponse
    {
        abort_unless($booking->vendor_id === $request->user()->vendor->id, 403);

        if ($booking->event_date->isFuture()) {
            throw ValidationException::withMessages(['booking' => __('flash.vendor.completion_too_early')]);
        }

        try {
            $completeBooking->handle($booking);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages(['booking' => $exception->getMessage()]);
        }

        return $this->answer($booking, __('flash.vendor.booking_completed', ['reference' => $booking->reference]));
    }

    public function cancel(Request $request, Booking $booking, CancelBooking $cancelBooking): JsonResponse
    {
        Gate::authorize('vendorCancel', $booking);

        $validated = $request->validate(['reason' => ['required', 'string', 'max:200']], attributes: ['reason' => __('fields.sebab')]);

        $cancelBooking->handle($booking, $request->user(), $validated['reason'], CancellationReason::Vendor);

        return $this->answer($booking, __('flash.vendor.booking_cancelled', ['reference' => $booking->reference]));
    }

    /** The booking as it is now, and a sentence saying what just happened. */
    public static function answer(Booking $booking, string $message): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'data' => new BookingDetailResource(self::loaded($booking->fresh())),
        ]);
    }

    private static function loaded(Booking $booking): Booking
    {
        return $booking->load(['user', 'payments.recorder', 'review', 'wedding']);
    }
}
