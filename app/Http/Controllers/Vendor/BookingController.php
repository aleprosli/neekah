<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\CreateBooking;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVendorBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\WeddingTimelineItem;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    /** Columns for components/ui/DataTable.vue, matching the keys data() returns. */
    /**
     * A constant cannot hold a function call, and these labels are
     * translated now.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'event_date', 'label' => __('props.vendor.tarikh'), 'sortable' => true],
            ['key' => 'customer', 'label' => __('props.vendor.pelanggan')],
            ['key' => 'package_name', 'label' => __('props.vendor.pakej')],
            ['key' => 'total', 'label' => __('props.vendor.jumlah'), 'sort' => 'total_amount', 'sortable' => true, 'align' => 'right'],
            ['key' => 'paid', 'label' => __('props.vendor.dibayar'), 'align' => 'right'],
            ['key' => 'status', 'label' => __('props.vendor.status'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.bookings.index', [
            'columns' => self::columns(),
            'filters' => [TableFilter::fromEnum(
                'status',
                BookingStatus::cases(),
                BookingStatus::tryFrom($request->string('status')->toString())?->value,
                $vendor->bookings()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            )],
        ]);
    }

    /**
     * A page of this vendor's bookings, filtered and sorted in the database.
     */
    public function data(Request $request): JsonResponse
    {
        $status = BookingStatus::tryFrom($request->string('status')->toString());
        $sort = in_array($request->string('sort')->toString(), ['event_date', 'total_amount'], true)
            ? $request->string('sort')->toString()
            : 'event_date';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

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
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'data' => $bookings->getCollection()->map(fn (Booking $booking): array => [
                'url' => route('vendor.bookings.show', $booking),
                'event_date' => $booking->event_date->translatedFormat('j M Y'),
                'customer' => $booking->user->name,
                'package_name' => $booking->package_name,
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

    public function create(Request $request): View
    {
        return view('vendor.bookings.create', [
            'props' => VueProps::for([
                'action' => route('vendor.bookings.store'),
                'cancelUrl' => route('vendor.bookings.index'),
                'createPackageUrl' => route('vendor.packages.create'),
                'packages' => $request->user()->vendor->packages()->active()->get()
                    ->map(fn (Package $package): array => [
                        'id' => $package->id,
                        'name' => $package->name,
                        'price' => (float) $package->price,
                    ])->values(),
                'commissionRate' => Booking::COMMISSION_RATE / 100,
                'old' => old(),
            ]),
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
            ->with('status', 'Booking '.$booking->reference.' direkod. Pelanggan boleh merekodkan bayaran mereka dari akaun mereka.');
    }

    public function show(Booking $booking): View
    {
        Gate::authorize('view', $booking);

        $booking->load(['user', 'package', 'payments.recorder', 'review']);

        // Vendors see only the timeline slots assigned to them, as the kertas kerja specifies.
        $timeline = $booking->wedding_id
            ? $booking->wedding->timelineItems()->where('vendor_id', $booking->vendor_id)->get()
            : collect();

        return view('vendor.bookings.show', [
            'booking' => $booking,
            'props' => VueProps::for([
                'booking' => [
                    'package_name' => $booking->package_name,
                    'event_date' => $booking->event_date->translatedFormat('l, j F Y'),
                    'created_at' => $booking->created_at->translatedFormat('j M Y, g:i A'),
                    'notes' => $booking->notes,
                    'customer' => [
                        'name' => $booking->user->name,
                        'contact' => collect([$booking->user->email, $booking->user->phone])->filter()->implode(' · '),
                    ],
                    'total' => 'RM'.number_format((float) $booking->total_amount, 2),
                    // Bookings made while Neekah is free carry no commission, and
                    // the vendor sees no commission line for them at all.
                    'has_commission' => $booking->hasCommission(),
                    'commission_rate' => number_format((float) $booking->commission_rate, 0),
                    'commission' => 'RM'.number_format((float) $booking->commission_amount, 2),
                    'payout' => 'RM'.number_format((float) $booking->total_amount - (float) $booking->commission_amount, 2),
                    'outstanding' => 'RM'.number_format($booking->outstandingAmount(), 2),
                    'payments' => $booking->payments
                        ->sortBy('created_at')
                        ->map(fn (Payment $payment): array => [
                            'reference' => $payment->reference,
                            'amount' => 'RM'.number_format((float) $payment->amount, 2),
                            'paid_on' => $payment->paid_on?->translatedFormat('j M Y'),
                            'note' => $payment->note,
                            'recorded_by' => $payment->recorder?->name ?? $booking->user->name,
                            'receipt_url' => $payment->receiptUrl(),
                            'status_label' => $payment->status->label(),
                            'status_tone' => $payment->status->tone(),
                            'awaiting' => $payment->isAwaitingVerification(),
                            'verify_url' => $payment->isAwaitingVerification() ? route('vendor.bookings.payments.verify', [$booking, $payment]) : null,
                            'reject_url' => $payment->isAwaitingVerification() ? route('vendor.bookings.payments.reject', [$booking, $payment]) : null,
                        ])->values(),
                    'review' => $booking->review ? [
                        'rating' => $booking->review->rating,
                        'comment' => $booking->review->comment,
                    ] : null,
                ],
                'timeline' => $timeline->map(fn (WeddingTimelineItem $item): array => [
                    'id' => $item->id,
                    'time' => $item->startsAtLabel(),
                    'title' => $item->title,
                    'location' => $item->location,
                    'notes' => $item->notes,
                ])->values(),
            ]),
        ]);
    }
}
