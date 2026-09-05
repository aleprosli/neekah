<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingSitePhotoRequest;
use App\Models\Wedding;
use App\Models\WeddingSitePhoto;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class WeddingSitePhotoController extends Controller
{
    public function store(StoreWeddingSitePhotoRequest $request, Wedding $wedding): RedirectResponse
    {
        $site = $wedding->site;
        abort_unless($site !== null, 404);

        $position = $site->photos()->count();

        foreach ($request->file('images') as $image) {
            $site->photos()->create([
                'path' => $image->store('sites/'.$wedding->id.'/galeri', 'public'),
                'caption' => $request->string('caption')->toString() ?: null,
                'sort_order' => $position++,
            ]);
        }

        return back()->with('status', count($request->file('images')).' gambar ditambah ke galeri.');
    }

    public function destroy(Wedding $wedding, WeddingSitePhoto $photo): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($photo->wedding_site_id === $wedding->site?->id, 404);

        Storage::disk('public')->delete($photo->path);
        $photo->delete();

        return back()->with('status', 'Gambar dipadam dari galeri.');
    }
}
