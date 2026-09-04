<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    /**
     * Financial view: gross transaction value, platform commission and vendor payouts.
     */
    public function index(Request $request): View
    {
        $status = PaymentStatus::tryFrom($request->string('status')->toString());

        $payments = Payment::query()
            ->with(['booking.vendor', 'booking.user'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $gross = (float) Payment::where('status', PaymentStatus::Paid)->sum('amount');
        $commission = (float) Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->sum('commission_amount');

        return view('admin.transactions', [
            'payments' => $payments,
            'status' => $status,
            'gross' => $gross,
            'commission' => $commission,
            'payout' => $gross - $commission,
            'outstanding' => (float) Payment::where('status', PaymentStatus::Pending)
                ->whereHas('booking', fn ($query) => $query->whereNot('status', BookingStatus::Cancelled))
                ->sum('amount'),
        ]);
    }
}
