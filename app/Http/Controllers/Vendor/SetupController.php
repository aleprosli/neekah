<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Support\ImageSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The setup cards on the dashboard each save one thing, so a vendor waiting
 * for approval never has to find the full profile editor. The full form
 * (ProfileController) needs every field at once; these take only what their
 * card asks for, and each card keeps its errors in its own bag.
 */
class SetupController extends Controller
{
    public function profile(Request $request): RedirectResponse
    {
        $data = $request->validateWithBag('setupProfile', [
            'tagline' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:2000'],
        ], attributes: [
            'tagline' => __('fields.tagline'),
            'description' => __('fields.penerangan'),
        ]);

        $request->user()->vendor->update($data);

        return $this->backToSetup(__('flash.vendor.setup_profile_saved'));
    }

    public function cover(Request $request, StoreOptimizedImage $storeImage, ImageSettings $images): RedirectResponse
    {
        $request->validateWithBag('setupCover', [
            'cover_image' => ['required', ...$images->uploadRules()],
        ], attributes: [
            'cover_image' => __('fields.gambar_muka_depan'),
        ]);

        $vendor = $request->user()->vendor;

        $storeImage->delete($vendor->cover_image);
        $vendor->update(['cover_image' => $storeImage->handle($request->file('cover_image'), 'vendors/'.$vendor->id)]);

        return $this->backToSetup(__('flash.vendor.setup_cover_saved'));
    }

    /**
     * No step is named, so the setup page opens the next one still to do:
     * saving a step is how a vendor moves through them.
     */
    private function backToSetup(string $status): RedirectResponse
    {
        return redirect()->route('vendor.dashboard')->with('status', $status);
    }
}
