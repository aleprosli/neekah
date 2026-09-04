<?php

namespace App\Http\Controllers\Vendor;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $vendor = $request->user()->vendor;

        $stats = [
            'upcoming' => $vendor->bookings()->where('status', BookingStatus::Confirmed)->whereDate('event_date', '>=', today())->count(),
            'pending' => $vendor->bookings()->where('status', BookingStatus::PendingPayment)->count(),
            'completed' => $vendor->completed_bookings_count,
            'open_enquiries' => $vendor->enquiries()->where('status', EnquiryStatus::Open)->count(),
            'paid_total' => (float) Payment::query()
                ->where('status', PaymentStatus::Paid)
                ->whereHas('booking', fn ($query) => $query->whereBelongsTo($vendor))
                ->sum('amount'),
        ];

        $upcomingBookings = $vendor->bookings()
            ->with('user')
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])
            ->whereDate('event_date', '>=', today())
            ->orderBy('event_date')
            ->limit(5)
            ->get();

        $checklist = [
            ['label' => 'Tambah tagline & penerangan', 'done' => filled($vendor->tagline) && filled($vendor->description), 'href' => route('vendor.profile.edit')],
            ['label' => 'Tambah sekurang-kurangnya 1 pakej', 'done' => $vendor->packages()->exists(), 'href' => route('vendor.packages.index')],
            ['label' => 'Muat naik portfolio', 'done' => $vendor->portfolioItems()->exists(), 'href' => route('vendor.portfolio.index')],
            ['label' => 'Tetapkan harga bermula', 'done' => (float) $vendor->price_from > 0, 'href' => route('vendor.profile.edit')],
        ];

        return view('vendor.dashboard', [
            'vendor' => $vendor,
            'stats' => $stats,
            'upcomingBookings' => $upcomingBookings,
            'checklist' => $checklist,
        ]);
    }
}
