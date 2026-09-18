<?php

namespace App\Http\Controllers\Customer;

use App\Actions\CreateBooking;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Vendor;
use App\Support\ImageSettings;
use App\Support\PaymentSettings;
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
            ->with('status', 'Booking '.$booking->reference.' dibuat. Berbincang dengan vendor, kemudian rekodkan bayaran anda di sini.');
    }

    public function show(Request $request, Booking $booking, PaymentSettings $paymentSettings, ImageSettings $images): View|RedirectResponse
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
                    'has_commission' => $booking->hasCommission(),
                    'commission_rate' => number_format((float) $booking->commission_rate, 0),
                ],
                'steps' => $this->progress($booking),
                'payments' => $this->paymentRows($booking),
                'cancelForm' => $request->user()->can('cancel', $booking) ? [
                    'action' => route('bookings.cancel', $booking),
                ] : null,
                'paymentForm' => $this->paymentForm($request, $booking, $paymentSettings, $images),
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
        $firstVerified = $booking->payments->first(fn (Payment $payment): bool => $payment->isPaid());

        $steps = [
            ['Booking dibuat', true, $booking->created_at],
            ['Bayaran direkod', $booking->payments->isNotEmpty(), $booking->payments->first()?->created_at],
            ['Bayaran disahkan vendor', $firstVerified !== null, $firstVerified?->verified_at],
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
     * Every payment the couple has written down, and where it stands.
     *
     * @return array<int, array<string, mixed>>
     */
    private function paymentRows(Booking $booking): array
    {
        return $booking->payments
            ->sortBy('created_at')
            ->map(fn (Payment $payment): array => [
                'reference' => $payment->reference,
                'amount' => 'RM'.number_format((float) $payment->amount, 2),
                'paid_on' => $payment->paid_on?->translatedFormat('j M Y'),
                'note' => $payment->note,
                'status_label' => $payment->status->label(),
                'status_tone' => $payment->status->tone(),
                'receipt_url' => $payment->receiptUrl(),
                'awaiting' => $payment->isAwaitingVerification(),
                'destroy_url' => $payment->isAwaitingVerification()
                    ? route('bookings.payments.destroy', [$booking, $payment])
                    : null,
            ])
            ->values()
            ->all();
    }

    /**
     * The form for writing down a payment, or the reason there isn't one.
     *
     * @return array<string, mixed>|null
     */
    private function paymentForm(Request $request, Booking $booking, PaymentSettings $settings, ImageSettings $images): ?array
    {
        if (! $settings->manualTransferEnabled() || ! $request->user()->can('recordPayment', $booking)) {
            return null;
        }

        $awaiting = (float) $booking->payments
            ->filter(fn (Payment $payment): bool => $payment->isAwaitingVerification())
            ->sum('amount');

        return [
            'action' => route('bookings.payments.store', $booking),
            'instructions' => $settings->instructions(),
            'imageHint' => $images->uploadHint('gambar resit penuh'),
            'today' => now()->toDateString(),
            'outstanding' => round($booking->outstandingAmount() - $awaiting, 2),
            'outstandingLabel' => 'RM'.number_format(max($booking->outstandingAmount() - $awaiting, 0), 2),
            'old' => [
                'amount' => old('amount', ''),
                'paid_on' => old('paid_on', now()->toDateString()),
                'note' => old('note', ''),
            ],
        ];
    }
}
