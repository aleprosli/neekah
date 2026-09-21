<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChecklistItemRequest;
use App\Models\ChecklistItem;
use Illuminate\Http\RedirectResponse;

class ChecklistItemController extends Controller
{
    public function store(StoreChecklistItemRequest $request): RedirectResponse
    {
        ChecklistItem::create([
            ...$request->attributesForItem(),
            'sort_order' => (int) ChecklistItem::where('checklist_section_id', $request->integer('checklist_section_id'))->max('sort_order') + 1,
        ]);

        return back()->with('status', __('flash.admin.item_added'));
    }

    public function update(StoreChecklistItemRequest $request, ChecklistItem $item): RedirectResponse
    {
        $item->update($request->attributesForItem());

        return back()->with('status', __('flash.admin.item_updated'));
    }

    /**
     * Only the master list loses the task. A couple who already has it keeps
     * it, because a tick is their record of work they did.
     */
    public function destroy(ChecklistItem $item): RedirectResponse
    {
        $item->delete();

        return back()->with('status', __('flash.admin.item_deleted'));
    }
}
