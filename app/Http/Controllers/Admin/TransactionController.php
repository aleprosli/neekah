<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /** Columns for components/ui/DataTable.vue. */
    private const COLUMNS = [
        ['key' => 'reference', 'label' => 'Rujukan', 'sortable' => true],
        ['key' => 'booking', 'label' => 'Booking'],
        ['key' => 'vendor', 'label' => 'Vendor'],
        ['key' => 'recorded_by', 'label' => 'Direkod oleh'],
        ['key' => 'amount', 'label' => 'Amaun', 'sortable' => true, 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'html'],
        ['key' => 'date', 'label' => 'Tarikh', 'sort' => 'paid_at', 'sortable' => true],
    ];

    /**
     * Financial view: gross transaction value, platform commission and vendor payouts.
     */
    public function index(Request $request): View
    {
        $gross = (float) Payment::where('status', PaymentStatus::Paid)->sum('amount');
        $commission = (float) Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->sum('commission_amount');

        return view('admin.transactions', [
            'columns' => self::COLUMNS,
            'status' => PaymentStatus::tryFrom($request->string('status')->toString()),
            'stats' => [
                ['label' => 'Gross transaction value', 'value' => 'RM'.number_format($gross, 2), 'hint' => 'Semua bayaran diterima'],
                ['label' => 'Komisen platform', 'value' => 'RM'.number_format($commission, 2), 'hint' => '8% daripada booking aktif'],
                ['label' => 'Payout vendor', 'value' => 'RM'.number_format($gross - $commission, 2), 'hint' => 'Selepas komisen'],
                ['label' => 'Menunggu pengesahan', 'value' => 'RM'.number_format((float) Payment::where('status', PaymentStatus::AwaitingVerification)
                    ->whereHas('booking', fn ($query) => $query->whereNot('status', BookingStatus::Cancelled))
                    ->sum('amount'), 2), 'hint' => 'Direkod pengantin, belum disahkan vendor'],
            ],
        ]);
    }

    /** A page of payments for the table. */
    public function data(Request $request): JsonResponse
    {
        $status = PaymentStatus::tryFrom($request->string('status')->toString());
        $sort = in_array($request->string('sort')->toString(), ['reference', 'amount', 'paid_at'], true)
            ? $request->string('sort')->toString()
            : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $payments = Payment::query()
            ->with(['booking.vendor', 'booking.user', 'recorder'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('reference', 'like', $like)
                    ->orWhereHas('booking', fn ($query) => $query->where('reference', 'like', $like))
                    ->orWhereHas('booking.vendor', fn ($query) => $query->where('name', 'like', $like)));
            })
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 25), 100));

        return response()->json([
            'data' => $payments->getCollection()->map(fn (Payment $payment): array => [
                'url' => route('admin.bookings.show', $payment->booking),
                'reference' => $payment->reference,
                'booking' => $payment->booking->reference,
                'vendor' => $payment->booking->vendor->name,
                'recorded_by' => $payment->recorder?->name ?? $payment->booking->user->name,
                'amount' => 'RM'.number_format((float) $payment->amount, 2),
                'status' => view('components.admin.status-pill', ['label' => $payment->status->label(), 'tone' => $payment->status->tone()])->render(),
                'date' => ($payment->paid_at ?? $payment->created_at)->translatedFormat('j M Y'),
            ])->all(),
            'meta' => [
                'total' => $payments->total(),
                'per_page' => $payments->perPage(),
                'current_page' => $payments->currentPage(),
                'last_page' => $payments->lastPage(),
            ],
        ]);
    }
}
