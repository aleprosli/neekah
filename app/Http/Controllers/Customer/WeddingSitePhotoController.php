<?php

namespace App\Http\Controllers\Customer;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingSitePhotoRequest;
use App\Models\Wedding;
use App\Models\WeddingSitePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WeddingSitePhotoController extends Controller
{
    public function store(StoreWeddingSitePhotoRequest $request, Wedding $wedding, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $site = $wedding->site;
        abort_unless($site !== null, 404);

        $position = $site->photos()->count();

        foreach ($request->file('images') as $image) {
            $site->photos()->create([
                'path' => $storeImage->handle($image, 'sites/'.$wedding->id.'/galeri'),
                'caption' => $request->string('caption')->toString() ?: null,
                'sort_order' => $position++,
            ]);
        }

        return back()->with('status', count($request->file('images')).' gambar ditambah ke galeri.');
    }

    public function destroy(Wedding $wedding, WeddingSitePhoto $photo, StoreOptimizedImage $storeImage): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($photo->wedding_site_id === $wedding->site?->id, 404);

        $storeImage->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Gambar dipadam dari galeri.');
    }
}
