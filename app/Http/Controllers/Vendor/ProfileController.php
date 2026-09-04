<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorProfileRequest;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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

    public function update(UpdateVendorProfileRequest $request): RedirectResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->safe()->except('cover_image');

        if ($request->hasFile('cover_image')) {
            if ($vendor->cover_image) {
                Storage::disk('public')->delete($vendor->cover_image);
            }

            $data['cover_image'] = $request->file('cover_image')->store('vendors/'.$vendor->id, 'public');
        }

        $vendor->update($data);

        return redirect()->route('vendor.profile.edit')->with('status', 'Profil dikemas kini.');
    }
}
