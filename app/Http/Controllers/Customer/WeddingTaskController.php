<?php

namespace App\Http\Controllers\Customer;

use App\Actions\SeedWeddingChecklist;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingTaskRequest;
use App\Models\Category;
use App\Models\Wedding;
use App\Models\WeddingTask;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class WeddingTaskController extends Controller
{
    /** The bucket a task the couple wrote themselves falls into. */
    private const OWN_TASKS = 'Tugasan saya sendiri';

    public function index(Request $request, SeedWeddingChecklist $seedChecklist): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        // Whatever admin has added to the master checklist since the last visit.
        $seedChecklist->handle($wedding);

        $tasks = $wedding->tasks()->with('category', 'completer', 'checklistItem', 'checklistSection')->get();

        $total = $tasks->count();
        $done = $tasks->whereNotNull('completed_at')->count();
        $overdue = $tasks->filter->isOverdue()->count();

        return view('customer.checklist', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'storeUrl' => route('weddings.tasks.store', $wedding),
                'stats' => [
                    ['label' => 'Progress', 'value' => ($total ? (int) round($done / $total * 100) : 0).'%', 'hint' => $done.' daripada '.$total.' selesai'],
                    ['label' => 'Belum selesai', 'value' => $total - $done, 'hint' => 'Termasuk tugasan akan datang'],
                    ['label' => 'Lewat', 'value' => $overdue, 'hint' => $overdue ? 'Perlu perhatian segera' : 'Semua mengikut jadual'],
                ],
                'progress' => [
                    'caption' => $done.' / '.$total,
                    'percent' => $total > 0 ? min(100, round($done / $total * 100)) : 0,
                ],
                'sections' => $this->sections($wedding, $tasks),
                'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
            ]),
        ]);
    }

    /**
     * The checklist as the couple walks it: one phase after another, each with
     * its own progress, and the sub-headings the phase carries inside it. What
     * the couple added themselves comes last, under its own heading.
     *
     * @param  Collection<int, WeddingTask>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function sections(Wedding $wedding, Collection $tasks): array
    {
        return $tasks
            ->sortBy(fn (WeddingTask $task): array => [
                $task->checklistSection?->sort_order ?? PHP_INT_MAX,
                $task->checklistSection?->id ?? PHP_INT_MAX,
                $task->checklistItem?->sort_order ?? PHP_INT_MAX,
                $task->sort_order,
            ])
            ->groupBy(fn (WeddingTask $task): string => $task->checklistSection?->title ?? self::OWN_TASKS)
            ->map(function (Collection $group, string $title) use ($wedding): array {
                $section = $group->first()->checklistSection;
                $done = $group->filter->isDone()->count();

                return [
                    'title' => $title,
                    'icon' => $section?->icon,
                    'note' => $section?->note,
                    'done' => $done,
                    'total' => $group->count(),
                    'percent' => (int) round($done / $group->count() * 100),
                    'overdue' => $group->filter->isOverdue()->count(),
                    'groups' => $group
                        ->groupBy(fn (WeddingTask $task): string => $task->checklistItem?->group ?? '')
                        ->map(fn (Collection $rows, string $heading): array => [
                            'heading' => $heading,
                            'tasks' => $rows->map(fn (WeddingTask $task): array => $this->shape($task, $wedding))->values(),
                        ])->values(),
                ];
            })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function shape(WeddingTask $task, Wedding $wedding): array
    {
        return [
            'id' => $task->id,
            'title' => $task->title,
            'notes' => $task->notes,
            'due' => $task->due_date ? ($task->isOverdue() ? 'Lewat ' : '').$task->due_date->translatedFormat('j M Y') : null,
            'overdue' => $task->isOverdue(),
            'done' => $task->isDone(),
            'completed' => $task->completed_at
                ? 'Selesai '.$task->completed_at->translatedFormat('j M Y').($task->completer ? ' oleh '.$task->completer->name : '')
                : null,
            'update_url' => route('weddings.tasks.update', [$wedding, $task]),
            'destroy_url' => route('weddings.tasks.destroy', [$wedding, $task]),
            'category' => $task->category ? [
                'name' => $task->category->name,
                'icon' => $task->category->icon,
                'illustration' => $task->category->illustrationUrl(),
                'vendors_url' => route('vendors.index', ['category' => $task->category->slug]),
            ] : null,
        ];
    }

    public function store(StoreWeddingTaskRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->tasks()->create([
            ...$request->validated(),
            'sort_order' => (int) $wedding->tasks()->max('sort_order') + 1,
        ]);

        return back()->with('status', 'Tugasan ditambah.');
    }

    /**
     * Tick or untick a task. Either partner may do it, and we record who.
     */
    public function update(Request $request, Wedding $wedding, WeddingTask $task): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($task->wedding_id === $wedding->id, 404);

        $done = $request->boolean('done');

        $task->update([
            'completed_at' => $done ? now() : null,
            'completed_by' => $done ? $request->user()->id : null,
        ]);

        return back();
    }

    public function destroy(Wedding $wedding, WeddingTask $task): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($task->wedding_id === $wedding->id, 404);

        $task->delete();

        return back()->with('status', 'Tugasan dipadam.');
    }
}
