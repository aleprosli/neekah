<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Platform overview: users, vendors, bookings, gross transaction value and commission.
     */
    public function __invoke(): View
    {
        $paidPayments = Payment::where('status', PaymentStatus::Paid);

        return view('admin.dashboard', [
            'stats' => [
                'customers' => User::where('role', UserRole::Customer)->count(),
                'vendors' => Vendor::count(),
                'pending_vendors' => Vendor::where('status', VendorStatus::Pending)->count(),
                'bookings' => Booking::count(),
                'active_bookings' => Booking::whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])->count(),
                'completed_bookings' => Booking::where('status', BookingStatus::Completed)->count(),
                'gross' => (float) $paidPayments->clone()->sum('amount'),
                'commission' => (float) Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->sum('commission_amount'),
            ],
            'pendingVendors' => Vendor::with(['category', 'user'])->where('status', VendorStatus::Pending)->latest()->limit(5)->get(),
            'recentBookings' => Booking::with(['vendor', 'user'])->latest()->orderByDesc('id')->limit(8)->get(),
            'topVendors' => Vendor::with('category')->approved()->orderByDesc('score')->limit(5)->get(),
        ]);
    }
}
