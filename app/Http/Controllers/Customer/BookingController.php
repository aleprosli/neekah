<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CreateBooking;
use App\Enums\BookingStatus;
use App\Enums\PaymentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Vendor;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $bookings = Booking::query()
            ->forCustomer($request->user())
            ->with(['vendor.category', 'payments'])
            ->latest()
            ->orderByDesc('id')
            ->paginate(10);

        $bookings->setCollection($bookings->getCollection()->map(fn (Booking $booking): array => [
            'reference' => $booking->reference,
            'url' => route('bookings.show', $booking),
            'vendor' => $booking->vendor->name,
            'summary' => $booking->package_name.' · '.$booking->event_date->translatedFormat('j M Y').' · '.$booking->reference,
            'total' => 'RM'.number_format((float) $booking->total_amount, 2),
            'paid' => 'RM'.number_format($booking->paidAmount(), 2),
            'status_label' => $booking->status->label(),
            'status_tone' => $booking->status->tone(),
            'category' => [
                'tone' => $booking->vendor->cover_tone,
                'icon' => $booking->vendor->category->icon,
                'illustration' => $booking->vendor->category->illustrationUrl(),
            ],
        ]));

        return view('customer.bookings.index', [
            'props' => VueProps::for([
                'bookings' => $bookings->items(),
                'findVendorsUrl' => route('vendors.index'),
                'pagination' => $bookings->hasPages() ? (string) $bookings->links() : '',
            ]),
        ]);
    }

    public function store(StoreBookingRequest $request, Vendor $vendor, CreateBooking $createBooking): RedirectResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $package = Package::findOrFail($request->integer('package_id'));

        $booking = $createBooking->handle($request->user(), $vendor, $package, [
            'event_date' => $request->date('event_date'),
            'wedding_id' => $request->filled('wedding_id')
                ? $request->integer('wedding_id')
                : $request->user()->weddings()->latest('event_date')->value('weddings.id'),
            'notes' => $request->string('notes')->toString() ?: null,
        ]);

        return redirect()
            ->route('bookings.show', $booking)
            ->with('status', 'Booking '.$booking->reference.' dibuat. Bayar deposit untuk sahkan tempahan anda.');
    }

    public function show(Request $request, Booking $booking): View|RedirectResponse
    {
        Gate::authorize('view', $booking);

        if ($request->user()->isVendor()) {
            return redirect()->route('vendor.bookings.show', $booking);
        }

        $booking->load(['vendor.category', 'package', 'payments', 'review']);

        return view('customer.bookings.show', [
            'booking' => $booking,
            'props' => VueProps::for([
                'booking' => [
                    'reference' => $booking->reference,
                    'event_date' => $booking->event_date->translatedFormat('l, j F Y'),
                    'package_name' => $booking->package_name,
                    'created_at' => $booking->created_at->translatedFormat('j M Y, g:i A'),
                    'notes' => $booking->notes,
                    'total' => 'RM'.number_format((float) $booking->total_amount, 2),
                    'paid' => 'RM'.number_format($booking->paidAmount(), 2),
                    'commission_rate' => number_format((float) $booking->commission_rate, 0),
                ],
                'steps' => $this->progress($booking),
                'payments' => $this->paymentRows($request, $booking),
                'review' => $booking->review ? [
                    'rating' => $booking->review->rating,
                    'comment' => $booking->review->comment,
                ] : null,
                'reviewForm' => $booking->review || ! $booking->canBeReviewed() ? null : [
                    'action' => route('bookings.review.store', $booking),
                    'comment' => old('comment', ''),
                    'fields' => collect([
                        'rating' => 'Keseluruhan',
                        'quality' => 'Kualiti',
                        'service' => 'Servis',
                        'communication' => 'Komunikasi',
                        'value' => 'Nilai',
                        'punctuality' => 'Ketepatan masa',
                    ])->map(fn (string $label, string $name): array => [
                        'name' => $name,
                        'label' => $label,
                        'value' => (int) old($name, 5),
                    ])->values(),
                ],
            ]),
        ]);
    }

    /**
     * How far along the booking is, as the steps a couple recognises.
     *
     * @return array<int, array{label: string, done: bool, at: string|null}>
     */
    private function progress(Booking $booking): array
    {
        $steps = [
            ['Booking dibuat', true, $booking->created_at],
            ['Deposit dibayar', (bool) $booking->depositPayment?->isPaid(), $booking->depositPayment?->paid_at],
            ['Booking disahkan', $booking->confirmed_at !== null, $booking->confirmed_at],
            ['Baki dibayar', (bool) $booking->balancePayment?->isPaid(), $booking->balancePayment?->paid_at],
            ['Majlis selesai', $booking->completed_at !== null, $booking->completed_at],
            ['Review diberi', $booking->review !== null, $booking->review?->created_at],
        ];

        return collect($steps)
            ->map(fn (array $step): array => [
                'label' => $step[0],
                'done' => $step[1],
                'at' => $step[2]?->translatedFormat('j M Y'),
            ])
            ->all();
    }

    /**
     * Each instalment, and whether this visitor can settle it right now.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentRows(Request $request, Booking $booking): array
    {
        $canPay = $request->user()->can('pay', $booking);

        return $booking->payments
            ->sortBy(fn (Payment $payment): int => $payment->type === PaymentType::Deposit ? 0 : 1)
            ->map(function (Payment $payment) use ($booking, $canPay): array {
                $isDeposit = $payment->type === PaymentType::Deposit;
                $awaitingDeposit = ! $isDeposit && ! $booking->depositPayment?->isPaid();
                $payable = ! $payment->isPaid()
                    && $booking->status !== BookingStatus::Cancelled
                    && ! $awaitingDeposit
                    && $canPay;

                return [
                    'label' => $payment->type->label().' ('.($isDeposit ? '40%' : '60%').')',
                    'amount' => 'RM'.number_format((float) $payment->amount, 2),
                    'paid' => $payment->isPaid(),
                    'note' => match (true) {
                        $payment->isPaid() => '✓ Dibayar '.$payment->paid_at->translatedFormat('j M Y').' · '.$payment->gateway_reference,
                        $booking->status === BookingStatus::Cancelled => 'Dibatalkan',
                        $awaitingDeposit => 'Boleh dibayar selepas deposit',
                        ! $canPay => 'Menunggu bayaran pelanggan',
                        default => null,
                    },
                    'pay_url' => $payable ? route('bookings.payments.store', [$booking, $payment]) : null,
                    'pay_label' => 'Bayar '.$payment->type->label().' sekarang',
                ];
            })
            ->values()
            ->all();
    }
}
