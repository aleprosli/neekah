<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\ChecklistItem;
use App\Models\Wedding;
use App\Models\WeddingTask;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeedWeddingChecklist
{
    /**
     * Copy the master checklist (Admin -> Checklist) onto a wedding, and fill a
     * budget row per category on a brand new one.
     *
     * The checklist part is additive and safe to run on every visit: an item
     * the wedding already carries is left exactly as the couple left it, and
     * an item an admin has since added simply appears. Nothing is ever removed
     * — a task the couple already ticked belongs to them, not to the template.
     */
    public function handle(Wedding $wedding): void
    {
        $this->syncTasks($wedding);

        if ($wedding->budgetItems()->doesntExist()) {
            $this->seedBudget($wedding);
        }
    }

    private function syncTasks(Wedding $wedding): void
    {
        $items = ChecklistItem::active()
            ->whereRelation('section', 'is_active', true)
            ->with('section')
            ->orderBy('checklist_section_id')
            ->ordered()
            ->get();

        if ($items->isEmpty()) {
            return;
        }

        $existing = $wedding->tasks()->whereNotNull('checklist_item_id')->pluck('checklist_item_id')->all();
        $missing = $items->whereNotIn('id', $existing);

        if ($missing->isEmpty()) {
            return;
        }

        $missing = $this->adoptMatchingTasks($wedding, $missing);

        $eventDate = Carbon::parse($wedding->event_date);
        $order = (int) $wedding->tasks()->max('sort_order');

        foreach ($missing as $item) {
            $wedding->tasks()->create([
                'checklist_item_id' => $item->id,
                'checklist_section_id' => $item->checklist_section_id,
                'category_id' => $item->category_id,
                'title' => $item->title,
                'notes' => $item->notes,
                'due_date' => $this->dueDate($eventDate, $item),
                'sort_order' => ++$order,
            ]);
        }
    }

    /**
     * A task the wedding already carries under the same name is the same task,
     * so it is filed under its phase rather than added a second time. That is
     * what stops a couple who was seeded from the old checklist — or who typed
     * "Tempah katering" themselves — from ending up with it twice.
     *
     * @param  Collection<int, ChecklistItem>  $missing
     * @return Collection<int, ChecklistItem>
     */
    private function adoptMatchingTasks(Wedding $wedding, Collection $missing): Collection
    {
        $loose = $wedding->tasks()->whereNull('checklist_item_id')->get()
            ->keyBy(fn (WeddingTask $task): string => Str::lower($task->title));

        if ($loose->isEmpty()) {
            return $missing;
        }

        return $missing->reject(function (ChecklistItem $item) use ($loose): bool {
            $task = $loose->get(Str::lower($item->title));

            if (! $task) {
                return false;
            }

            $task->update([
                'checklist_item_id' => $item->id,
                'checklist_section_id' => $item->checklist_section_id,
                'category_id' => $task->category_id ?? $item->category_id,
            ]);

            return true;
        });
    }

    /**
     * An item with no months_before has no deadline — everything after the
     * akad is like that. A wedding booked at short notice gets tasks due today
     * rather than in the past.
     */
    private function dueDate(Carbon $eventDate, ChecklistItem $item): ?string
    {
        if ($item->months_before === null) {
            return null;
        }

        $due = $eventDate->copy()->subMonths($item->months_before);

        return $due->isPast() ? today()->toDateString() : $due->toDateString();
    }

    /**
     * Spread the total budget across categories using the proportions from the
     * worked example in the kertas kerja, so the couple starts with a real plan.
     */
    private function seedBudget(Wedding $wedding): void
    {
        /** @var Collection<string, int> $categories */
        $categories = Category::active()->pluck('id', 'slug');

        $shares = [
            'catering' => 0.33, 'venue' => 0.17, 'decoration' => 0.13, 'pelamin' => 0.10,
            'photography' => 0.08, 'videography' => 0.08, 'makeup' => 0.05, 'bridal' => 0.03,
            'invitation' => 0.01, 'cake' => 0.01, 'emcee' => 0.01,
        ];

        $budget = (float) $wedding->budget;

        foreach ($categories as $slug => $categoryId) {
            $wedding->budgetItems()->create([
                'category_id' => $categoryId,
                'planned_amount' => round($budget * ($shares[$slug] ?? 0), 2),
            ]);
        }
    }
}
