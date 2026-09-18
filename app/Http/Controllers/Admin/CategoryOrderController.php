<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderCategoriesRequest;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class CategoryOrderController extends Controller
{
    /**
     * Save the arrangement an admin dragged into place. Category order is what
     * the marketplace filters and the landing page tiles follow.
     */
    public function update(ReorderCategoriesRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('items') as $item) {
                Category::whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
