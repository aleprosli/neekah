<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request): View
    {
        $status = VendorStatus::tryFrom($request->string('status')->toString());

        $vendors = Vendor::query()
            ->with(['category', 'user'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->string('q')->trim()->toString(), function ($query, string $keyword) {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('city', 'like', $like));
            })
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.vendors.index', [
            'vendors' => $vendors,
            'status' => $status,
            'counts' => Vendor::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load(['category', 'user', 'packages', 'portfolioItems']);

        return view('admin.vendors.show', [
            'vendor' => $vendor,
            'categories' => Category::active()->ordered()->get(),
            'bookingCount' => $vendor->bookings()->count(),
        ]);
    }
}
