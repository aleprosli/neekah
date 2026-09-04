<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePortfolioItemRequest;
use App\Models\PortfolioItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PortfolioItemController extends Controller
{
    public function index(Request $request): View
    {
        return view('vendor.portfolio.index', [
            'items' => $request->user()->vendor->portfolioItems()->get(),
        ]);
    }

    public function store(StorePortfolioItemRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $position = $vendor->portfolioItems()->count();

        foreach ($request->file('images') as $image) {
            $vendor->portfolioItems()->create([
                'path' => $image->store('portfolio/'.$vendor->id, 'public'),
                'caption' => $request->string('caption')->toString() ?: null,
                'sort_order' => $position++,
            ]);
        }

        return redirect()->route('vendor.portfolio.index')->with('status', count($request->file('images')).' gambar dimuat naik.');
    }

    public function destroy(Request $request, PortfolioItem $item): RedirectResponse
    {
        abort_unless($item->vendor_id === $request->user()->vendor?->id, 403);

        Storage::disk('public')->delete($item->path);
        $item->delete();

        return redirect()->route('vendor.portfolio.index')->with('status', 'Gambar dipadam.');
    }
}
