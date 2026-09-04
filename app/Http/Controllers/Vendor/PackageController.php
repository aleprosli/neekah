<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePackageRequest;
use App\Models\Package;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PackageController extends Controller
{
    public function index(Request $request): View
    {
        return view('vendor.packages.index', [
            'packages' => $request->user()->vendor->packages()->get(),
        ]);
    }

    public function create(): View
    {
        return view('vendor.packages.form', ['package' => new Package(['is_active' => true])]);
    }

    public function store(StorePackageRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        $vendor->packages()->create([
            ...$request->safe()->except('features', 'is_active'),
            'features' => $request->featureList(),
            'is_active' => $request->boolean('is_active', true),
            'sort_order' => $vendor->packages()->count(),
        ]);

        $this->syncPriceFrom($request);

        return redirect()->route('vendor.packages.index')->with('status', 'Pakej ditambah.');
    }

    public function edit(Package $package): View
    {
        Gate::authorize('update', $package);

        return view('vendor.packages.form', ['package' => $package]);
    }

    public function update(StorePackageRequest $request, Package $package): RedirectResponse
    {
        $package->update([
            ...$request->safe()->except('features', 'is_active'),
            'features' => $request->featureList(),
            'is_active' => $request->boolean('is_active'),
        ]);

        $this->syncPriceFrom($request);

        return redirect()->route('vendor.packages.index')->with('status', 'Pakej dikemas kini.');
    }

    public function destroy(Request $request, Package $package): RedirectResponse
    {
        Gate::authorize('delete', $package);

        $package->delete();
        $this->syncPriceFrom($request);

        return redirect()->route('vendor.packages.index')->with('status', 'Pakej dipadam.');
    }

    /**
     * Keep the marketplace "from" price aligned with the cheapest active package.
     */
    private function syncPriceFrom(Request $request): void
    {
        $vendor = $request->user()->vendor;
        $cheapest = $vendor->packages()->active()->min('price');

        if ($cheapest !== null) {
            $vendor->update(['price_from' => $cheapest]);
        }
    }
}
