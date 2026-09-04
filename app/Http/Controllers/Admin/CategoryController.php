<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Models\Category;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.categories.index', [
            'categories' => Category::withCount('vendors')->ordered()->get(),
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
