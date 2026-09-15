<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostImageRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

/**
 * Receives an image dropped into the article editor and hands back its URL.
 */
class PostImageController extends Controller
{
    public function __invoke(StorePostImageRequest $request, StoreOptimizedImage $storeImage): JsonResponse
    {
        $path = $storeImage->handle($request->file('image'), 'blog/'.now()->format('Y/m'));

        return response()->json(['url' => Storage::disk('public')->url($path)], 201);
    }
}
