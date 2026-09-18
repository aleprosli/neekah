<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReorderChecklistRequest;
use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ChecklistOrderController extends Controller
{
    /**
     * Save the arrangement an admin dragged into place. An item may also have
     * moved to another phase, so its section is written with its position.
     */
    public function update(ReorderChecklistRequest $request): JsonResponse
    {
        DB::transaction(function () use ($request): void {
            foreach ($request->validated('sections') ?? [] as $section) {
                ChecklistSection::whereKey($section['id'])->update(['sort_order' => $section['sort_order']]);
            }

            foreach ($request->validated('items') ?? [] as $item) {
                ChecklistItem::whereKey($item['id'])->update([
                    'checklist_section_id' => $item['checklist_section_id'],
                    'sort_order' => $item['sort_order'],
                ]);
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
