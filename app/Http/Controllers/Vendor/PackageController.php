<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\StoreOptimizedImage;
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

    public function store(StorePackageRequest $request, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $vendor = $request->user()->vendor;

        $vendor->packages()->create([
            ...$request->safe()->except('features', 'is_active', 'image', 'remove_image'),
            'features' => $request->featureList(),
            'image' => $request->hasFile('image') ? $storeImage->handle($request->file('image'), 'packages/'.$vendor->id) : null,
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

    public function update(StorePackageRequest $request, Package $package, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $package->update([
            ...$request->safe()->except('features', 'is_active', 'image', 'remove_image'),
            'features' => $request->featureList(),
            'is_active' => $request->boolean('is_active'),
            ...$this->imageChange($request, $package, $storeImage),
        ]);

        $this->syncPriceFrom($request);

        return redirect()->route('vendor.packages.index')->with('status', 'Pakej dikemas kini.');
    }

    public function destroy(Request $request, Package $package, StoreOptimizedImage $storeImage): RedirectResponse
    {
        Gate::authorize('delete', $package);

        $storeImage->delete($package->image);
        $package->delete();
        $this->syncPriceFrom($request);

        return redirect()->route('vendor.packages.index')->with('status', 'Pakej dipadam.');
    }

    /**
     * The image column to write, if the vendor asked for a new one or for the
     * current one to go. The old file is removed either way, so a replaced
     * image does not linger on disk.
     *
     * @return array<string, string|null>
     */
    private function imageChange(StorePackageRequest $request, Package $package, StoreOptimizedImage $storeImage): array
    {
        if ($request->hasFile('image')) {
            $storeImage->delete($package->image);

            return ['image' => $storeImage->handle($request->file('image'), 'packages/'.$package->vendor_id)];
        }

        if ($request->boolean('remove_image')) {
            $storeImage->delete($package->image);

            return ['image' => null];
        }

        return [];
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
