<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\StoreOptimizedImage;
use App\Enums\PriceUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorProfileRequest;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\ImageSettings;
use App\Support\SocialLinks;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit(Request $request, ImageSettings $images): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.profile', [
            'props' => VueProps::for([
                'action' => route('vendor.profile.update'),
                'vendor' => [
                    'name' => old('name', $vendor->name),
                    'category_id' => (int) old('category_id', $vendor->category_id),
                    'state' => old('state', $vendor->state),
                    'city' => old('city', $vendor->city),
                    'tagline' => old('tagline', $vendor->tagline),
                    'description' => old('description', $vendor->description),
                    'phone' => old('phone', $vendor->phone),
                    'whatsapp' => old('whatsapp', $vendor->whatsapp),
                    'social_links' => old('social_links', $vendor->social_links ?? []),
                    'price_from' => old('price_from', $vendor->price_from),
                    'price_unit' => old('price_unit', $vendor->price_unit->value),
                    'cover_tone' => old('cover_tone', $vendor->cover_tone),
                    'cover_image_url' => $vendor->cover_image ? Storage::disk('public')->url($vendor->cover_image) : null,
                    'logo_url' => $vendor->logoUrl(),
                    'initial' => mb_substr($vendor->name, 0, 1),
                ],
                'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
                'states' => Vendor::STATES,
                'socialPlatforms' => collect(SocialLinks::PLATFORMS)
                    ->map(fn (array $details, string $platform): array => ['key' => $platform, 'label' => $details['label'], 'placeholder' => $details['placeholder']])
                    ->values()
                    ->all(),
                'tones' => UpdateVendorProfileRequest::TONES,
                'priceUnits' => collect(PriceUnit::cases())
                    ->map(fn (PriceUnit $unit): array => ['value' => $unit->value, 'label' => 'Setiap '.$unit->label()])
                    ->all(),
                'imageHint' => $images->uploadHint('landskap 1920 × 1080px').'. Jika tiada gambar, warna latar digunakan.',
                'logoHint' => $images->uploadHint('persegi 512 × 512px').'. Jika tiada logo, huruf pertama nama perniagaan digunakan.',
            ]),
        ]);
    }

    public function update(UpdateVendorProfileRequest $request, StoreOptimizedImage $storeImage): RedirectResponse|JsonResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->safe()->except(['cover_image', 'logo', 'remove_logo']);

        if (array_key_exists('social_links', $data)) {
            $data['social_links'] = array_filter($data['social_links'] ?? []) ?: null;
        }

        if ($request->hasFile('cover_image')) {
            $storeImage->delete($vendor->cover_image);
            $data['cover_image'] = $storeImage->handle($request->file('cover_image'), 'vendors/'.$vendor->id);
        }

        if ($request->hasFile('logo')) {
            $storeImage->delete($vendor->logo);
            $data['logo'] = $storeImage->handle($request->file('logo'), 'vendors/'.$vendor->id);
        }

        if ($request->boolean('remove_logo')) {
            $storeImage->delete($vendor->logo);
            $data['logo'] = null;
        }

        $vendor->update($data);

        return $this->redirectOrJson($request, route('vendor.profile.edit'), 'Profil dikemas kini.');
    }
}
