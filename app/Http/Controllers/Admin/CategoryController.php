<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'props' => VueProps::for([
                'storeUrl' => route('admin.categories.store'),
                'categories' => Category::withCount('vendors')->ordered()->get()
                    ->map(fn (Category $category): array => [
                        'id' => $category->id,
                        'name' => $category->name,
                        'icon' => $category->icon,
                        'illustration' => $category->illustrationUrl(),
                        'examples' => $category->examples,
                        'sort_order' => $category->sort_order,
                        'is_active' => $category->is_active,
                        'vendors_count' => $category->vendors_count,
                        'update_url' => route('admin.categories.update', $category),
                        'destroy_url' => route('admin.categories.destroy', $category),
                    ])->values(),
            ]),
        ]);
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->attributesForCategory());

        return back()->with('status', 'Kategori ditambah.');
    }

    public function update(StoreCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->attributesForCategory());

        return back()->with('status', 'Kategori dikemas kini.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->vendors()->exists()) {
            return back()->withErrors(['category' => 'Kategori ini masih digunakan oleh vendor. Nyahaktifkan sahaja.']);
        }

        $category->delete();

        return back()->with('status', 'Kategori dipadam.');
    }
}
