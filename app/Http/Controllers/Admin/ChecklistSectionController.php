<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreChecklistSectionRequest;
use App\Models\Category;
use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
use App\Models\Wedding;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ChecklistSectionController extends Controller
{
    /**
     * The master checklist every couple starts from, unpaged: an admin drags
     * phases and tasks into the order the couple walks through them, which
     * only works while the whole list is on screen.
     */
    public function index(): View
    {
        $sections = ChecklistSection::with(['items' => fn ($query) => $query->ordered(), 'items.category'])
            ->ordered()
            ->get();

        $items = $sections->flatMap->items;

        return view('admin.checklist.index', [
            'props' => VueProps::for([
                'sectionStoreUrl' => route('admin.checklist.sections.store'),
                'itemStoreUrl' => route('admin.checklist.items.store'),
                'orderUrl' => route('admin.checklist.order'),
                'stats' => [
                    ['label' => 'Fasa', 'value' => $sections->count()],
                    ['label' => 'Jumlah tugasan', 'value' => $items->count()],
                    ['label' => 'Tugasan aktif', 'value' => $items->where('is_active', true)->count()],
                    ['label' => 'Majlis terlibat', 'value' => Wedding::count()],
                ],
                'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
                'sections' => $sections->map(fn (ChecklistSection $section): array => [
                    'id' => $section->id,
                    'title' => $section->title,
                    'icon' => $section->icon,
                    'note' => $section->note,
                    'is_active' => $section->is_active,
                    'update_url' => route('admin.checklist.sections.update', $section),
                    'destroy_url' => route('admin.checklist.sections.destroy', $section),
                    'items' => $section->items->map(fn (ChecklistItem $item): array => [
                        'id' => $item->id,
                        'checklist_section_id' => $item->checklist_section_id,
                        'title' => $item->title,
                        'group' => $item->group,
                        'notes' => $item->notes,
                        'months_before' => $item->months_before,
                        'is_active' => $item->is_active,
                        'category_id' => $item->category_id,
                        'category' => $item->category?->name,
                        'update_url' => route('admin.checklist.items.update', $item),
                        'destroy_url' => route('admin.checklist.items.destroy', $item),
                    ])->values(),
                ])->values(),
            ]),
        ]);
    }

    public function store(StoreChecklistSectionRequest $request): RedirectResponse
    {
        ChecklistSection::create([
            ...$request->attributesForSection(),
            'sort_order' => (int) ChecklistSection::max('sort_order') + 1,
        ]);

        return back()->with('status', 'Fasa ditambah.');
    }

    public function update(StoreChecklistSectionRequest $request, ChecklistSection $section): RedirectResponse
    {
        $section->update($request->attributesForSection());

        return back()->with('status', 'Fasa dikemas kini.');
    }

    /**
     * Deleting a phase takes its tasks with it, but only from the master list:
     * the copies already on a couple's checklist are their own records and
     * keep whatever they ticked.
     */
    public function destroy(ChecklistSection $section): RedirectResponse
    {
        $section->delete();

        return back()->with('status', 'Fasa dipadam. Checklist pengantin sedia ada tidak berubah.');
    }
}
