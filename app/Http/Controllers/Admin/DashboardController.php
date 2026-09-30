<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserRole;
use App\Enums\VendorStatus;
use App\Enums\ViolationStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use App\Models\Vendor;
use App\Models\VendorViolation;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    /**
     * Platform overview: users, vendors, bookings, gross transaction value and commission.
     */
    public function __invoke(): View
    {
        $paidPayments = Payment::where('status', PaymentStatus::Paid);

        $stats = [
            'customers' => User::where('role', UserRole::Customer)->count(),
            'vendors' => Vendor::count(),
            'pending_vendors' => Vendor::where('status', VendorStatus::Pending)->count(),
            'bookings' => Booking::count(),
            'active_bookings' => Booking::whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed])->count(),
            'completed_bookings' => Booking::where('status', BookingStatus::Completed)->count(),
            'gross' => (float) $paidPayments->clone()->sum('amount'),
            'commission' => (float) Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])->sum('commission_amount'),
            'open_violations' => VendorViolation::where('status', ViolationStatus::Open)->count(),
        ];

        return view('admin.dashboard', [
            'stats' => $stats,
            'props' => VueProps::for([
                'stats' => [
                    ['label' => __('props.admin.pengantin_2'), 'value' => number_format($stats['customers']), 'hint' => __('props.admin.akaun_customer'), 'href' => route('admin.users.index', ['role' => 'customer'])],
                    ['label' => __('props.admin.vendor_2'), 'value' => number_format($stats['vendors']), 'hint' => __('props.units.awaiting_approval', ['count' => $stats['pending_vendors']]), 'href' => route('admin.vendors.index')],
                    ['label' => __('props.admin.tempahan'), 'value' => number_format($stats['bookings']), 'hint' => __('props.units.active_completed', ['active' => $stats['active_bookings'], 'completed' => $stats['completed_bookings']]), 'href' => route('admin.bookings.index')],
                    ['label' => __('props.admin.komisen_platform'), 'value' => 'RM'.number_format($stats['commission'], 2), 'hint' => __('props.admin.gtv_rm').number_format($stats['gross'], 2), 'href' => route('admin.payments.index')],
                ],
                'alert' => $stats['open_violations'] > 0 ? [
                    'count' => $stats['open_violations'],
                    'url' => route('admin.violations.index', ['status' => 'open']),
                ] : null,
                'pendingUrl' => route('admin.vendors.index', ['status' => 'pending']),
                'bookingsUrl' => route('admin.bookings.index'),
                'pending' => Vendor::with(['category', 'user'])->where('status', VendorStatus::Pending)->latest()->limit(5)->get()
                    ->map(fn (Vendor $vendor): array => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'summary' => $vendor->category->name.' · '.$vendor->city.', '.$vendor->state,
                        'tone' => $vendor->cover_tone,
                        'icon' => $vendor->category->icon,
                        'illustration' => $vendor->category->illustrationUrl(),
                        'url' => route('admin.vendors.show', $vendor),
                        'approve_url' => route('admin.vendors.status', $vendor),
                    ])->values(),
                'topVendors' => Vendor::with('category')->approved()->orderByDesc('score')->limit(5)->get()
                    ->map(fn (Vendor $vendor): array => [
                        'id' => $vendor->id,
                        'name' => $vendor->name,
                        'summary' => $vendor->tier->label().' · ★ '.number_format((float) $vendor->rating_avg, 1).' ('.$vendor->reviews_count.')',
                        'score' => number_format((float) $vendor->score, 1),
                        'url' => route('admin.vendors.show', $vendor),
                    ])->values(),
                'recentBookings' => Booking::with(['vendor', 'user'])->latest()->orderByDesc('id')->limit(8)->get()
                    ->map(fn (Booking $booking): array => [
                        'reference' => $booking->reference,
                        'url' => route('admin.bookings.show', $booking),
                        'vendor' => $booking->vendor->name,
                        'customer' => $booking->user->name,
                        'total' => 'RM'.number_format((float) $booking->total_amount, 2),
                        'commission' => 'RM'.number_format((float) $booking->commission_amount, 2),
                        'status_label' => $booking->status->label(),
                        'status_tone' => $booking->status->tone(),
                    ])->values(),
            ]),
        ]);
    }
}
