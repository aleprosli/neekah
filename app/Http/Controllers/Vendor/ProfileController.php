<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorProfileRequest;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('vendor.profile', [
            'vendor' => $request->user()->vendor,
            'categories' => Category::active()->ordered()->get(),
            'states' => Vendor::STATES,
            'tones' => UpdateVendorProfileRequest::TONES,
        ]);
    }

    public function update(UpdateVendorProfileRequest $request, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->safe()->except('cover_image');

        if ($request->hasFile('cover_image')) {
            $storeImage->delete($vendor->cover_image);
            $data['cover_image'] = $storeImage->handle($request->file('cover_image'), 'vendors/'.$vendor->id);
        }

        $vendor->update($data);

        return redirect()->route('vendor.profile.edit')->with('status', 'Profil dikemas kini.');
    }
}
