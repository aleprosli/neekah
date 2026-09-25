<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorFeaturesRequest;
use App\Models\Vendor;
use App\Support\VendorFeatureSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Which parts of the vendor area each plan opens. A single vendor can be given
 * an exception on their own admin page (VendorFeatureOverrideController).
 */
class VendorFeatureController extends Controller
{
    public function index(VendorFeatureSettings $settings): View
    {
        return view('admin.vendor-features.index', [
            'features' => VendorFeature::cases(),
            'plans' => VendorFeatureSettings::PLANS,
            'settings' => $settings,
            'overridden' => Vendor::query()->whereNotNull('feature_overrides')->count(),
        ]);
    }

    public function update(UpdateVendorFeaturesRequest $request, VendorFeatureSettings $settings): RedirectResponse
    {
        $settings->save($request->settings());

        return back()->with('status', __('flash.admin.vendor_features_saved'));
    }
}
