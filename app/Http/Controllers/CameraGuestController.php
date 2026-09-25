<?php

namespace App\Http\Controllers;

use App\Models\CameraAlbum;
use App\Support\Seo;
use Illuminate\Contracts\View\View;

/**
 * The page a guest reaches from the QR. Guests are not signed in; the token
 * in the address is what lets them in.
 */
class CameraGuestController extends Controller
{
    public function show(CameraAlbum $album, Seo $seo): View
    {
        $seo->noindex();
        $album->loadMissing('wedding');

        return view('camera.show', [
            'album' => $album,
            'closed' => ! $album->isActive(),
        ]);
    }
}
