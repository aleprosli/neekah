<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\ImageSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    /**
     * The whole list, unpaged: an admin drags categories into the order the
     * marketplace shows them in, which only works while every row is on screen.
     */
    public function index(ImageSettings $images): View
    {
        $categories = Category::withCount('vendors')->ordered()->get();

        return view('admin.categories.index', [
            'props' => VueProps::for([
                'storeUrl' => route('admin.categories.store'),
                'orderUrl' => route('admin.categories.order'),
                'imageHint' => $images->uploadHint('512 × 512px, latar lutsinar'),
                'stats' => [
                    ['label' => 'Jumlah kategori', 'value' => $categories->count()],
                    ['label' => 'Jumlah vendor', 'value' => Vendor::count()],
                    ['label' => 'Kategori aktif', 'value' => $categories->where('is_active', true)->count()],
                    ['label' => 'Tidak aktif', 'value' => $categories->where('is_active', false)->count()],
                ],
                'categories' => $categories
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon,
                        'illustration' => $category->illustrationUrl(),
                        'has_upload' => filled($category->image),
                        'examples' => $category->examples,
                        'is_active' => $category->is_active,
                        'vendors_count' => $category->vendors_count,
                        'update_url' => route('admin.categories.update', $category),
                        'destroy_url' => route('admin.categories.destroy', $category),
                    ])->values(),
            ]),
        ]);
    }

    public function store(StoreCategoryRequest $request, StoreOptimizedImage $storeImage): RedirectResponse|JsonResponse
    {
        $category = Category::create([
            ...$request->attributesForCategory(),
            'sort_order' => $request->integer('sort_order') ?: Category::max('sort_order') + 1,
        ]);

        if ($request->hasFile('image')) {
            $category->update(['image' => $storeImage->handle($request->file('image'), 'categories/'.$category->id)]);
        }

        return $this->redirectOrJson($request, route('admin.categories.index'), 'Kategori ditambah.');
    }

    public function update(StoreCategoryRequest $request, Category $category, StoreOptimizedImage $storeImage): RedirectResponse|JsonResponse
    {
        $attributes = $request->attributesForCategory();

        if ($request->hasFile('image')) {
            $storeImage->delete($category->image);
            $attributes['image'] = $storeImage->handle($request->file('image'), 'categories/'.$category->id);
        }

        if ($request->boolean('remove_image')) {
            $storeImage->delete($category->image);
            $attributes['image'] = null;
        }

        $category->update($attributes);

        return $this->redirectOrJson($request, route('admin.categories.index'), 'Kategori dikemas kini.');
    }

    public function destroy(Category $category, StoreOptimizedImage $storeImage): RedirectResponse
    {
        if ($category->vendors()->exists()) {
            return back()->withErrors(['category' => 'Kategori ini masih digunakan oleh vendor. Nyahaktifkan sahaja.']);
        }

        $storeImage->delete($category->image);
        $category->delete();

        return back()->with('status', 'Kategori dipadam.');
    }
}
