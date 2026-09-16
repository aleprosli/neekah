<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\CreateBooking;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    /** Columns for components/ui/DataTable.vue, matching the keys data() returns. */
    private const COLUMNS = [
        ['key' => 'event_date', 'label' => 'Tarikh', 'sortable' => true],
        ['key' => 'customer', 'label' => 'Pelanggan'],
        ['key' => 'package_name', 'label' => 'Pakej'],
        ['key' => 'total', 'label' => 'Jumlah', 'sortable' => true, 'align' => 'right'],
        ['key' => 'paid', 'label' => 'Dibayar', 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'html'],
    ];

    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.bookings.index', [
            'columns' => self::COLUMNS,
            'status' => BookingStatus::tryFrom($request->string('status')->toString()),
            'counts' => $vendor->bookings()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * A page of this vendor's bookings, filtered and sorted in the database.
     */
    public function data(Request $request): JsonResponse
    {
        $status = BookingStatus::tryFrom($request->string('status')->toString());
        $sort = in_array($request->string('sort')->toString(), ['event_date', 'total'], true)
            ? $request->string('sort')->toString()
            : 'event_date';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $bookings = $request->user()->vendor->bookings()
            ->with(['user', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('reference', 'like', $like)
                    ->orWhere('package_name', 'like', $like)
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $like)));
            })
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'data' => $bookings->getCollection()->map(fn (Booking $booking): array => [
                'url' => route('vendor.bookings.show', $booking),
                'event_date' => $booking->event_date->translatedFormat('j M Y'),
                'customer' => $booking->user->name,
                'package_name' => $booking->package_name,
                'total' => 'RM'.number_format((float) $booking->total, 2),
                'paid' => 'RM'.number_format((float) $booking->payments->where('status', PaymentStatus::Paid)->sum('amount'), 2),
                'status' => view('components.booking-status', ['status' => $booking->status])->render(),
            ])->all(),
            'meta' => [
                'total' => $bookings->total(),
                'per_page' => $bookings->perPage(),
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
            ],
        ]);
    }

    public function create(Request $request): View
    {
        return view('vendor.bookings.create', [
            'packages' => $request->user()->vendor->packages()->active()->get(),
        ]);
    }

    public function store(StoreVendorBookingRequest $request, CreateBooking $createBooking): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $package = Package::findOrFail($request->integer('package_id'));

        $customer = $request->customer();

        $booking = $createBooking->handle($customer, $vendor, $package, [
            'event_date' => $request->date('event_date'),
            'wedding_id' => $customer->weddings()->latest('event_date')->value('weddings.id'),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('vendor.bookings.show', $booking)
            ->with('status', 'Booking '.$booking->reference.' direkod. Pelanggan boleh bayar deposit dari akaun mereka.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['user', 'package', 'payments', 'review']);

        return view('vendor.bookings.show', [
            'booking' => $booking,
            // Vendors see only the timeline slots assigned to them, as the kertas kerja specifies.
            'timelineItems' => $booking->wedding_id
                ? $booking->wedding->timelineItems()->where('vendor_id', $booking->vendor_id)->get()
                : collect(),
        ]);
    }
}
