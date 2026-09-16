<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingTaskRequest;
use App\Models\Category;
use App\Models\Wedding;
use App\Models\WeddingTask;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WeddingTaskController extends Controller
{
    public function index(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $tasks = $wedding->tasks()->with('category', 'completer')->get();

        $total = $tasks->count();
        $done = $tasks->whereNotNull('completed_at')->count();
        $overdue = $tasks->filter->isOverdue()->count();

        $shape = fn (WeddingTask $task): array => [
            'id' => $task->id,
            'title' => $task->title,
            'due' => $task->due_date ? ($task->isOverdue() ? 'Lewat ' : '').$task->due_date->translatedFormat('j M Y') : null,
            'overdue' => $task->isOverdue(),
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
                'todo' => $tasks->filter(fn (WeddingTask $task): bool => ! $task->isDone())->map($shape)->values(),
                'done' => $tasks->filter(fn (WeddingTask $task): bool => $task->isDone())->map($shape)->values(),
                'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
            ]),
        ]);
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
