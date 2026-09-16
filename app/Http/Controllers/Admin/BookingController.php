<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /** Columns the DataTable draws, and the keys each row is expected to carry. */
    private const COLUMNS = [
        ['key' => 'reference', 'label' => 'Rujukan', 'sortable' => true],
        ['key' => 'event_date', 'label' => 'Majlis', 'sortable' => true],
        ['key' => 'vendor', 'label' => 'Vendor'],
        ['key' => 'customer', 'label' => 'Pengantin'],
        ['key' => 'total', 'label' => 'Jumlah', 'sortable' => true, 'align' => 'right'],
        ['key' => 'paid', 'label' => 'Dibayar', 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'html'],
    ];

    public function index(Request $request): View
    {
        return view('admin.bookings.index', [
            'columns' => self::COLUMNS,
            'status' => BookingStatus::tryFrom($request->string('status')->toString()),
            'counts' => Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    /**
     * The rows behind the table, one page at a time. Sorting and searching run
     * in the database, so the browser never holds more than a page of bookings.
     */
    public function data(Request $request): JsonResponse
    {
        $status = BookingStatus::tryFrom($request->string('status')->toString());
        $sort = in_array($request->string('sort')->toString(), ['reference', 'event_date', 'total'], true)
            ? $request->string('sort')->toString()
            : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $bookings = Booking::query()
            ->with(['vendor', 'user', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('reference', 'like', $like)
                    ->orWhereHas('vendor', fn ($query) => $query->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            })
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'data' => $bookings->getCollection()->map(fn (Booking $booking): array => [
                'url' => route('admin.bookings.show', $booking),
                'reference' => $booking->reference,
                'event_date' => $booking->event_date->translatedFormat('j M Y'),
                'vendor' => $booking->vendor->name,
                'customer' => $booking->user->name,
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

    public function show(Booking $booking): View
    {
        $booking->load(['vendor.category', 'user', 'payments', 'review', 'wedding']);

        return view('admin.bookings.show', ['booking' => $booking]);
    }
}
