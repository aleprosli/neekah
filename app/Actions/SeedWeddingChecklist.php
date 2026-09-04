<?php

namespace App\Actions;

use App\Models\Category;
use App\Models\Wedding;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SeedWeddingChecklist
{
    /**
     * The starter checklist from the kertas kerja, with each task due a sensible
     * number of months before the event so the couple has a real timeline.
     *
     * @return array<int, array{title: string, category: string|null, months_before: int}>
     */
    public const TEMPLATE = [
        ['title' => 'Tetapkan bajet dan senarai tetamu', 'category' => null, 'months_before' => 12],
        ['title' => 'Tempah venue', 'category' => 'venue', 'months_before' => 10],
        ['title' => 'Tempah katering', 'category' => 'catering', 'months_before' => 9],
        ['title' => 'Tempah photographer', 'category' => 'photography', 'months_before' => 8],
        ['title' => 'Tempah videographer', 'category' => 'videography', 'months_before' => 8],
        ['title' => 'Tempah pelamin', 'category' => 'pelamin', 'months_before' => 7],
        ['title' => 'Tempah hiasan dewan', 'category' => 'decoration', 'months_before' => 6],
        ['title' => 'Tempah makeup artist', 'category' => 'makeup', 'months_before' => 6],
        ['title' => 'Fitting baju pengantin', 'category' => 'bridal', 'months_before' => 5],
        ['title' => 'Tempah emcee', 'category' => 'emcee', 'months_before' => 4],
        ['title' => 'Tempah kek kahwin', 'category' => 'cake', 'months_before' => 3],
        ['title' => 'Tempah hiburan atau live band', 'category' => 'entertainment', 'months_before' => 3],
        ['title' => 'Hantar kad jemputan', 'category' => 'invitation', 'months_before' => 2],
        ['title' => 'Sahkan jumlah tetamu dengan katering', 'category' => 'catering', 'months_before' => 1],
        ['title' => 'Jelaskan baki bayaran semua vendor', 'category' => null, 'months_before' => 1],
        ['title' => 'Sediakan wedding timeline hari majlis', 'category' => null, 'months_before' => 1],
    ];

    /**
     * Fill a new wedding with the starter checklist and a budget row per category.
     * Existing tasks and budget rows are left alone.
     */
    public function handle(Wedding $wedding): void
    {
        $categories = Category::active()->pluck('id', 'slug');

        if ($wedding->tasks()->doesntExist()) {
            $this->seedTasks($wedding, $categories);
        }

        if ($wedding->budgetItems()->doesntExist()) {
            $this->seedBudget($wedding, $categories);
        }
    }

    /**
     * @param  Collection<string, int>  $categories
     */
    private function seedTasks(Wedding $wedding, $categories): void
    {
        $eventDate = Carbon::parse($wedding->event_date);

        foreach (self::TEMPLATE as $index => $task) {
            $due = $eventDate->copy()->subMonths($task['months_before']);

            $wedding->tasks()->create([
                'category_id' => $task['category'] ? $categories[$task['category']] ?? null : null,
                'title' => $task['title'],
                // A wedding booked at short notice gets tasks due today rather than in the past.
                'due_date' => $due->isPast() ? today() : $due->toDateString(),
                'sort_order' => $index,
            ]);
        }
    }

    /**
     * Spread the total budget across categories using the proportions from the
     * worked example in the kertas kerja, so the couple starts with a real plan.
     *
     * @param  Collection<string, int>  $categories
     */
    private function seedBudget(Wedding $wedding, $categories): void
    {
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
