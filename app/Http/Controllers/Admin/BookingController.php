<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    /** Columns the DataTable draws, and the keys each row is expected to carry. */
    /**
     * A constant cannot hold a function call, and these labels are
     * translated now.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => __('props.admin.rujukan'), 'sortable' => true],
            ['key' => 'event_date', 'label' => __('props.admin.majlis'), 'sortable' => true],
            ['key' => 'vendor', 'label' => __('props.admin.vendor')],
            ['key' => 'customer', 'label' => __('props.admin.pengantin')],
            ['key' => 'total', 'label' => __('props.admin.jumlah'), 'sort' => 'total_amount', 'sortable' => true, 'align' => 'right'],
            ['key' => 'paid', 'label' => __('props.admin.dibayar'), 'align' => 'right'],
            ['key' => 'status', 'label' => __('props.admin.status_2'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        return view('admin.bookings.index', [
            'columns' => self::columns(),
            'filters' => [TableFilter::fromEnum(
                'status',
                BookingStatus::cases(),
                BookingStatus::tryFrom($request->string('status')->toString())?->value,
                Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            )],
        ]);
    }

    /**
     * The rows behind the table, one page at a time. Sorting and searching run
     * in the database, so the browser never holds more than a page of bookings.
     */
    public function data(Request $request): JsonResponse
    {
        $status = BookingStatus::tryFrom($request->string('status')->toString());
        $sort = in_array($request->string('sort')->toString(), ['reference', 'event_date', 'total_amount'], true)
            ? $request->string('sort')->toString()
            : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        // The chips count what the search left, so they never disagree with
        // the table under them.
        $matching = Booking::query()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('reference', 'like', $like)
                    ->orWhereHas('vendor', fn ($query) => $query->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like)));
            });

        $bookings = $matching->clone()
            ->with(['vendor', 'user', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'data' => $bookings->getCollection()->map(fn (Booking $booking): array => [
                'url' => route('admin.bookings.show', $booking),
                'reference' => $booking->reference,
                'event_date' => $booking->event_date->translatedFormat('j M Y'),
                'vendor' => $booking->vendor->name,
                'customer' => $booking->user->name,
                'total' => 'RM'.number_format((float) $booking->total_amount, 2),
                'paid' => 'RM'.number_format((float) $booking->payments->where('status', PaymentStatus::Paid)->sum('amount'), 2),
                'status' => view('components.booking-status', ['status' => $booking->status])->render(),
            ])->all(),
            'filters' => ['status' => TableFilter::countsByColumn($matching, 'status')],
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

        return view('admin.bookings.show', [
            'booking' => $booking,
            'props' => VueProps::for([
                'booking' => [
                    'package_name' => $booking->package_name,
                    'created_at' => $booking->created_at->translatedFormat('j M Y, g:i A'),
                    'notes' => $booking->notes,
                    'wedding' => $booking->wedding?->title,
                    'vendor' => [
                        'name' => $booking->vendor->name,
                        'category' => $booking->vendor->category->name,
                        'url' => route('admin.vendors.show', $booking->vendor),
                    ],
                    'customer' => [
                        'name' => $booking->user->name,
                        'email' => $booking->user->email,
                    ],
                    'total' => 'RM'.number_format((float) $booking->total_amount, 2),
                    'paid' => 'RM'.number_format($booking->paidAmount(), 2),
                    'commission_rate' => number_format((float) $booking->commission_rate, 0),
                    'commission' => 'RM'.number_format((float) $booking->commission_amount, 2),
                    'payout' => 'RM'.number_format((float) $booking->total_amount - (float) $booking->commission_amount, 2),
                    'payments' => $booking->payments
                        ->sortBy('created_at')
                        ->map(fn (Payment $payment): array => [
                            'label' => $payment->paid_on?->translatedFormat('j M Y') ?? 'Tarikh tidak direkod',
                            'reference' => $payment->reference,
                            'amount' => 'RM'.number_format((float) $payment->amount, 2),
                            'status' => $payment->status->label(),
                            'is_paid' => $payment->isPaid(),
                        ])->values(),
                    'review' => $booking->review ? [
                        'rating' => $booking->review->rating,
                        'comment' => $booking->review->comment,
                    ] : null,
                ],
            ]),
        ]);
    }
}
