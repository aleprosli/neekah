<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTimelineItemRequest;
use App\Models\Vendor;
use App\Models\Wedding;
use App\Models\WeddingTimelineItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class WeddingTimelineController extends Controller
{
    public function index(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        return view('customer.timeline', [
            'wedding' => $wedding,
            'items' => $wedding->timelineItems()->with('vendor.category')->get(),
            'bookedVendors' => $this->bookedVendors($wedding),
        ]);
    }

    public function store(StoreTimelineItemRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->timelineItems()->create($request->validated());

        return back()->with('status', 'Aktiviti ditambah ke timeline.');
    }

    public function update(StoreTimelineItemRequest $request, Wedding $wedding, WeddingTimelineItem $item): RedirectResponse
    {
        abort_unless($item->wedding_id === $wedding->id, 404);

        $item->update($request->validated());

        return back()->with('status', 'Aktiviti dikemas kini.');
    }

    public function destroy(Wedding $wedding, WeddingTimelineItem $item): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($item->wedding_id === $wedding->id, 404);

        $item->delete();

        return back()->with('status', 'Aktiviti dipadam.');
    }

    /**
     * Only vendors the couple has actually booked can be attached to a slot.
     *
     * @return Collection<int, Vendor>
     */
    private function bookedVendors(Wedding $wedding)
    {
        return $wedding->bookings()
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed, BookingStatus::Completed])
            ->with('vendor.category')
            ->get()
            ->pluck('vendor')
            ->unique('id')
            ->sortBy('name')
            ->values();
    }
}
