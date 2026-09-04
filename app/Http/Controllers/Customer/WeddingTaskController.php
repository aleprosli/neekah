<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingTaskRequest;
use App\Models\Category;
use App\Models\Wedding;
use App\Models\WeddingTask;
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

        return view('customer.checklist', [
            'wedding' => $wedding,
            'tasks' => $tasks,
            'categories' => Category::active()->ordered()->get(),
            'done' => $tasks->whereNotNull('completed_at')->count(),
            'overdue' => $tasks->filter->isOverdue()->count(),
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
