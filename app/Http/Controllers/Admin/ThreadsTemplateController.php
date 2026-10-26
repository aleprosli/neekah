<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreThreadsTemplateRequest;
use App\Models\ThreadsTemplate;
use Illuminate\Http\RedirectResponse;

/**
 * The bank the Threads planner writes from. Deleting a template leaves the
 * posts already written from it as they are.
 */
class ThreadsTemplateController extends Controller
{
    public function store(StoreThreadsTemplateRequest $request): RedirectResponse
    {
        ThreadsTemplate::create($request->attributesForTemplate());

        return back()->with('status', __('flash.admin.threads_template_created'));
    }

    public function update(StoreThreadsTemplateRequest $request, ThreadsTemplate $template): RedirectResponse
    {
        $template->update($request->attributesForTemplate());

        return back()->with('status', __('flash.admin.threads_template_updated'));
    }

    public function destroy(ThreadsTemplate $template): RedirectResponse
    {
        $template->delete();

        return back()->with('status', __('flash.admin.threads_template_deleted'));
    }
}
