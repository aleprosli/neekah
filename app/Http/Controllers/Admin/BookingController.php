<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): View
    {
        $status = BookingStatus::tryFrom($request->string('status')->toString());

        $bookings = Booking::query()
            ->with(['vendor', 'user', 'payments'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->string('q')->trim()->toString(), function ($query, string $keyword) {
                $like = '%'.$keyword.'%';
                $query->where('reference', 'like', $like)
                    ->orWhereHas('vendor', fn ($query) => $query->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.bookings.index', [
            'bookings' => $bookings,
            'status' => $status,
            'counts' => Booking::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Booking $booking): View
    {
        $booking->load(['vendor.category', 'user', 'payments', 'review', 'wedding']);

        return view('admin.bookings.show', ['booking' => $booking]);
    }
}
