<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorFeatureOverridesRequest;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;

/**
 * One vendor's exceptions to what their plan opens.
 */
class VendorFeatureOverrideController extends Controller
{
    public function update(UpdateVendorFeatureOverridesRequest $request, Vendor $vendor): RedirectResponse
    {
        $vendor->update(['feature_overrides' => $request->overrides()]);

        return back()->with('status', __('flash.admin.vendor_overrides_saved', ['vendor' => $vendor->name]));
    }
}
