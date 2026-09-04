<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Enums\EnquiryStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Wedding command center: budget, booked categories, upcoming payments.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $wedding = $user->weddings()->latest('event_date')->first();

        $bookings = $user->bookings()
            ->with(['vendor.category', 'payments'])
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed, BookingStatus::Completed])
            ->orderBy('event_date')
            ->get();

        $categories = Category::active()->ordered()->get();
        $bookedCategoryIds = $bookings->pluck('vendor.category_id')->unique();

        return view('customer.dashboard', [
            'wedding' => $wedding,
            'bookings' => $bookings,
            'categories' => $categories,
            'bookedCategoryIds' => $bookedCategoryIds,
            'committed' => (float) $bookings->sum('total_amount'),
            'paid' => $bookings->sum(fn ($booking) => $booking->paidAmount()),
            'pendingPayments' => $bookings->flatMap->payments->where('status', PaymentStatus::Pending)->count(),
            'openEnquiries' => $user->enquiries()->where('status', EnquiryStatus::Replied)->count(),
        ]);
    }
}
