<?php

namespace App\Http\Controllers\Customer;

use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Category;
use App\Support\VueProps;
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
        $wedding = $user->weddings()->with(['members', 'invitations' => fn ($query) => $query->pending()])->latest('event_date')->first();

        $bookings = Booking::query()
            ->forCustomer($user)
            ->with(['vendor.category', 'payments'])
            ->whereIn('status', [BookingStatus::PendingPayment, BookingStatus::Confirmed, BookingStatus::Completed])
            ->orderBy('event_date')
            ->get();

        $categories = Category::active()->ordered()->get();
        $bookedCategoryIds = $bookings->pluck('vendor.category_id')->unique();

        $committed = (float) $bookings->sum('total_amount');
        $budget = (float) ($wedding?->budget ?? 0);
        $paid = (float) $bookings->sum(fn (Booking $booking): float => $booking->paidAmount());
        $awaitingVerification = $bookings->flatMap->payments->where('status', PaymentStatus::AwaitingVerification)->count();

        return view('customer.dashboard', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'hasWedding' => $wedding !== null,
                'createUrl' => route('weddings.create'),
                'findVendorsUrl' => route('vendors.index'),
                'stats' => $wedding === null ? [] : [
                    ['label' => 'Bajet', 'value' => 'RM'.number_format($budget), 'hint' => 'Baki RM'.number_format($budget - $committed)],
                    ['label' => 'Ditempah', 'value' => 'RM'.number_format($committed), 'hint' => 'Dibayar RM'.number_format($paid)],
                    ['label' => 'Vendor', 'value' => $bookedCategoryIds->count().' / '.$categories->count(), 'hint' => ($categories->count() ? round($bookedCategoryIds->count() / $categories->count() * 100) : 0).'% kategori ditempah'],
                    ['label' => 'Menunggu pengesahan', 'value' => $awaitingVerification, 'hint' => 'Bayaran direkod, belum disahkan vendor', 'href' => route('bookings.index')],
                ],
                'budget' => $wedding === null ? null : [
                    'caption' => 'RM'.number_format($committed).' / RM'.number_format($budget),
                    'percent' => $budget > 0 ? min(100, round($committed / $budget * 100)) : 0,
                    'over' => $committed > $budget,
                    'overBy' => 'RM'.number_format(max(0, $committed - $budget)),
                ],
                'bookings' => $bookings->map(fn (Booking $booking): array => [
                    'reference' => $booking->reference,
                    'url' => route('bookings.show', $booking),
                    'vendor' => $booking->vendor->name,
                    'summary' => $booking->vendor->category->name.' · '.$booking->package_name,
                    'total' => 'RM'.number_format((float) $booking->total_amount),
                    'status_label' => $booking->status->label(),
                    'status_tone' => $booking->status->tone(),
                    'category' => [
                        'tone' => $booking->vendor->cover_tone,
                        'icon' => $booking->vendor->category->icon,
                        'illustration' => $booking->vendor->category->illustrationUrl(),
                    ],
                ])->values(),
                'categories' => $categories->map(fn (Category $category): array => [
                    'name' => $category->name,
                    'icon' => $category->icon,
                    'illustration' => $category->illustrationUrl(),
                    'booked' => $bookedCategoryIds->contains($category->id),
                    'url' => $bookedCategoryIds->contains($category->id)
                        ? route('bookings.index')
                        : route('vendors.index', ['category' => $category->slug]),
                ])->values(),
            ]),
        ]);
    }
}
