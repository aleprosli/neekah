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
use App\Support\States;
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
                    'category_ids' => array_map('intval', old('category_ids', $vendor->categories->modelKeys())),
                    'state' => old('state', $vendor->state),
                    'service_states' => old('service_states', $vendor->serviceStates()),
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
                'maxCategories' => UpdateVendorProfileRequest::MAX_CATEGORIES,
                'states' => States::options(),
                'socialPlatforms' => collect(SocialLinks::PLATFORMS)
                    ->map(fn (array $details, string $platform): array => ['key' => $platform, 'label' => $details['label'], 'placeholder' => $details['placeholder']])
                    ->values()
                    ->all(),
                'tones' => UpdateVendorProfileRequest::TONES,
                'priceUnits' => collect(PriceUnit::cases())
                    ->map(fn (PriceUnit $unit): array => ['value' => $unit->value, 'label' => __('props.vendor.setiap').$unit->label()])
                    ->all(),
                'imageHint' => __('props.vendor.image_hint_fallback', ['hint' => $images->uploadHint(__('props.vendor.landskap_1920'))]),
                'logoHint' => __('props.vendor.logo_hint_fallback', ['hint' => $images->uploadHint(__('props.vendor.persegi_512'))]),
            ]),
        ]);
    }

    public function update(UpdateVendorProfileRequest $request, StoreOptimizedImage $storeImage): RedirectResponse|JsonResponse
    {
        $vendor = $request->user()->vendor;
        $data = $request->safe()->except(['cover_image', 'logo', 'remove_logo', 'category_ids']);

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

        // The primary category is put back by the model, so a vendor cannot
        // save themselves out of the category their card and profile show.
        $vendor->categories()->sync($request->safe()->collect('category_ids')->push($vendor->category_id)->unique()->all());

        return $this->redirectOrJson($request, route('vendor.profile.edit'), 'Profil dikemas kini.');
    }
}
