<?php

use App\Models\ChecklistItem;
use App\Models\ChecklistSection;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\ChecklistSeeder;

beforeEach(function () {
    $this->seed([CategorySeeder::class, ChecklistSeeder::class]);
    $this->admin = User::factory()->admin()->create();
});

it('shows every phase and its tasks on one page, in the order they are walked', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.checklist.index'))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $sections = collect($props['sections']);

            return $sections->count() === ChecklistSection::count()
                && $sections->first()['title'] === ChecklistSection::ordered()->first()->title
                && count($sections->first()['items']) === ChecklistSection::ordered()->first()->items()->count()
                && collect($props['stats'])->firstWhere('label', 'Jumlah tugasan')['value'] === ChecklistItem::count();
        });
});

it('adds a phase at the end and a task at the end of its phase', function () {
    $section = ChecklistSection::ordered()->first();
    $lastPosition = (int) $section->items()->max('sort_order');

    $this->actingAs($this->admin)
        ->post(route('admin.checklist.sections.store'), ['title' => 'Bulan Madu', 'icon' => '🌴', 'is_active' => 1])
        ->assertRedirect();

    $added = ChecklistSection::whereTranslated('title', 'Bulan Madu')->sole();
    expect($added->sort_order)->toBe((int) ChecklistSection::where('id', '!=', $added->id)->max('sort_order') + 1);

    $this->actingAs($this->admin)
        ->post(route('admin.checklist.items.store'), [
            'checklist_section_id' => $section->id,
            'title' => 'Tempah kereta pengantin',
            'months_before' => 3,
            'is_active' => 1,
        ])
        ->assertRedirect();

    $item = ChecklistItem::whereTranslated('title', 'Tempah kereta pengantin')->sole();
    expect($item->sort_order)->toBe($lastPosition + 1)
        ->and($item->checklist_section_id)->toBe($section->id);
});

it('keeps a task without a deadline when no month is given', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.checklist.items.store'), [
            'checklist_section_id' => ChecklistSection::first()->id,
            'title' => 'Rancang takaful',
            'months_before' => '',
            'is_active' => 1,
        ])
        ->assertRedirect();

    expect(ChecklistItem::whereTranslated('title', 'Rancang takaful')->sole()->months_before)->toBeNull();
});

it('edits a phase and a task', function () {
    $section = ChecklistSection::ordered()->first();
    $item = $section->items()->ordered()->first();

    $this->actingAs($this->admin)
        ->put(route('admin.checklist.sections.update', $section), ['title' => 'Perancangan', 'icon' => '📝', 'note' => 'Mula awal.', 'is_active' => 1])
        ->assertRedirect();

    $this->actingAs($this->admin)
        ->put(route('admin.checklist.items.update', $item), [
            'checklist_section_id' => $section->id,
            'title' => 'Bincang dengan keluarga',
            'group' => 'Awal',
            'is_active' => 0,
        ])
        ->assertRedirect();

    expect($section->fresh()->title)->toBe('Perancangan')
        ->and($section->fresh()->note)->toBe('Mula awal.')
        ->and($item->fresh()->title)->toBe('Bincang dengan keluarga')
        ->and($item->fresh()->group)->toBe('Awal')
        ->and($item->fresh()->is_active)->toBeFalse();
});

it('saves the order an admin dragged, including a task moved to another phase', function () {
    [$first, $second] = ChecklistSection::ordered()->take(2)->get()->all();
    $moved = $first->items()->ordered()->first();

    $this->actingAs($this->admin)
        ->putJson(route('admin.checklist.order'), [
            'sections' => [
                ['id' => $second->id, 'sort_order' => 0],
                ['id' => $first->id, 'sort_order' => 1],
            ],
            'items' => [
                ['id' => $moved->id, 'checklist_section_id' => $second->id, 'sort_order' => 0],
            ],
        ])
        ->assertOk();

    expect(ChecklistSection::ordered()->first()->id)->toBe($second->id)
        ->and($moved->fresh()->checklist_section_id)->toBe($second->id)
        ->and($moved->fresh()->sort_order)->toBe(0);
});

it('takes a phase tasks with it when the phase is deleted', function () {
    $section = ChecklistSection::ordered()->first();
    $items = $section->items()->count();

    $this->actingAs($this->admin)->delete(route('admin.checklist.sections.destroy', $section))->assertRedirect();

    expect(ChecklistSection::find($section->id))->toBeNull()
        ->and(ChecklistItem::where('checklist_section_id', $section->id)->count())->toBe(0)
        ->and(ChecklistItem::count())->toBeGreaterThan(0)
        ->and($items)->toBeGreaterThan(0);
});

it('lets nobody but an admin touch the master checklist', function () {
    $section = ChecklistSection::first();
    $couple = User::factory()->create();

    $this->get(route('admin.checklist.index'))->assertRedirect(route('login'));
    $this->actingAs($couple)->get(route('admin.checklist.index'))->assertForbidden();
    $this->actingAs($couple)->post(route('admin.checklist.sections.store'), ['title' => 'Penyusup'])->assertForbidden();
    $this->actingAs($couple)->delete(route('admin.checklist.sections.destroy', $section))->assertForbidden();
    $this->actingAs($couple)
        ->putJson(route('admin.checklist.order'), ['sections' => [['id' => $section->id, 'sort_order' => 0]]])
        ->assertForbidden();
});
