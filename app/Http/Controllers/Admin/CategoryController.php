<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOptimizedImage;
use App\Casts\Translatable;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\ImageSettings;
use App\Support\Locales;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * The whole list, unpaged: an admin drags categories into the order the
     * marketplace shows them in, which only works while every row is on screen.
     *
     * A form that failed validation comes back open, on the category it was
     * editing, with what the admin typed — the page is one island, so without
     * `old` a typo in the English name would wipe the whole form.
     */
    public function index(Request $request, ImageSettings $images): View
    {
        $categories = Category::query()
            ->withCount(['vendors', 'vendors as approved_vendors_count' => fn (Builder $vendors) => $vendors->approved()])
            ->ordered()
            ->get();

        $active = $categories->where('is_active', true)->count();
        $empty = $categories->where('vendors_count', 0)->count();

        return view('admin.categories.index', [
            'props' => VueProps::for([
                'storeUrl' => route('admin.categories.store'),
                'orderUrl' => route('admin.categories.order'),
                'locales' => collect(Locales::codes())
                    ->map(fn (string $code): array => ['code' => $code, 'label' => Locales::label($code)])
                    ->all(),
                'imageHint' => $images->uploadHint(__('props.admin.category_image_size')),
                'old' => $request->session()->hasOldInput() ? [
                    'category_id' => $request->old('category_id'),
                    'name' => $request->old('name', []),
                    'icon' => $request->old('icon', ''),
                    'examples' => $request->old('examples', []),
                    'is_active' => (bool) $request->old('is_active', true),
                ] : null,
                'stats' => [
                    ['label' => __('props.admin.jumlah_kategori'), 'value' => $categories->count(), 'hint' => __('props.admin.category_stat_order')],
                    ['label' => __('props.admin.kategori_aktif'), 'value' => $active, 'hint' => __('props.admin.category_stat_inactive', ['count' => $categories->count() - $active])],
                    ['label' => __('props.admin.jumlah_vendor'), 'value' => Vendor::count(), 'hint' => __('props.admin.category_stat_approved', ['count' => Vendor::approved()->count()])],
                    ['label' => __('props.admin.category_stat_empty'), 'value' => $empty, 'hint' => __('props.admin.category_stat_empty_hint')],
                ],
                'categories' => $categories
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        // Every language side by side, so an admin can fill in
                        // the one that is missing without leaving the row.
                        'names' => Translatable::all($category, 'name'),
                        'icon' => $category->icon,
                        'illustration' => $category->illustrationUrl(),
                        'has_upload' => filled($category->image),
                        'examples' => $category->examples,
                        'examples_all' => Translatable::all($category, 'examples'),
                        'is_active' => $category->is_active,
                        'vendors_count' => $category->vendors_count,
                        'vendors_label' => trans_choice('props.admin.category_vendors', $category->vendors_count, ['count' => $category->vendors_count]),
                        'approved_label' => __('props.admin.category_vendors_approved', ['count' => $category->approved_vendors_count]),
                        'marketplace_url' => $category->is_active ? route('vendors.index', ['category' => $category->slug]) : null,
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

        return $this->redirectOrJson($request, route('admin.categories.index'), __('flash.admin.category_added'));
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

        return $this->redirectOrJson($request, route('admin.categories.index'), __('flash.admin.category_updated'));
    }

    public function destroy(Category $category, StoreOptimizedImage $storeImage): RedirectResponse
    {
        if ($category->vendors()->exists()) {
            return back()->withErrors(['category' => __('flash.admin.category_in_use')]);
        }

        $storeImage->delete($category->image);
        $category->delete();

        return back()->with('status', __('flash.admin.category_deleted'));
    }
}
