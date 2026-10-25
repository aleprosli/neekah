<?php

namespace App\Http\Controllers\Admin;

use App\Actions\PlanThreadsPosts;
use App\Actions\StoreOptimizedImage;
use App\Enums\ThreadsPostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreThreadsPostRequest;
use App\Models\ThreadsPost;
use App\Support\ThreadsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

/**
 * Posts in the queue: written by hand, edited, rewritten, sent now or taken
 * off. Only a post that has not gone out may be touched
 * (ThreadsPostStatus::isEditable).
 */
class ThreadsPostController extends Controller
{
    public function __construct(private StoreOptimizedImage $images) {}

    public function store(StoreThreadsPostRequest $request): RedirectResponse
    {
        ThreadsPost::create([
            ...$this->attributesFrom($request),
            'image_path' => $this->storeImage($request->file('image')),
            'status' => ThreadsPostStatus::Scheduled,
            'user_id' => $request->user()->id,
        ]);

        return back()->with('status', __('flash.admin.threads_post_created'));
    }

    public function update(StoreThreadsPostRequest $request, ThreadsPost $post): RedirectResponse
    {
        abort_unless($post->status->isEditable(), 403);

        $image = [];

        if ($request->file('image') || $request->boolean('remove_image')) {
            $this->images->delete($post->image_path);
            $image = ['image_path' => $this->storeImage($request->file('image')), 'image_url' => null];
        }

        $post->update([...$this->attributesFrom($request), ...$image, ...self::backInQueue()]);

        return back()->with('status', __('flash.admin.threads_post_updated'));
    }

    /**
     * Taken off the queue, not deleted: the row keeps its slot, so the
     * planner does not put another post in its place.
     */
    public function destroy(ThreadsPost $post): RedirectResponse
    {
        abort_unless($post->status->isEditable(), 403);

        $post->update(['status' => ThreadsPostStatus::Cancelled]);

        return back()->with('status', __('flash.admin.threads_post_cancelled'));
    }

    /**
     * Due now, so the next minute's run sends it. A failed or missed post
     * goes back into the queue the same way.
     */
    public function publish(ThreadsPost $post, ThreadsSettings $settings): RedirectResponse
    {
        abort_unless($post->status->isEditable(), 403);

        $post->update(['scheduled_at' => now(), ...self::backInQueue()]);

        return back()->with('status', $settings->isEnabled()
            ? __('flash.admin.threads_post_publishing')
            : __('flash.admin.threads_post_waiting_for_switch'));
    }

    public function rewrite(ThreadsPost $post, PlanThreadsPosts $plan): RedirectResponse
    {
        abort_unless($post->status->isEditable() && $post->slot_at !== null, 403);

        if (! $plan->rewrite($post)) {
            return back()->withErrors(['threads' => __('flash.admin.threads_nothing_to_rewrite', ['type' => $post->type->label()])]);
        }

        $post->update(self::backInQueue());

        return back()->with('status', __('flash.admin.threads_post_rewritten'));
    }

    /**
     * @return array<string, mixed>
     */
    private function attributesFrom(StoreThreadsPostRequest $request): array
    {
        return [
            'type' => $request->validated('type'),
            'text' => trim($request->string('text')->toString()),
            'scheduled_at' => $request->date('scheduled_at'),
            'link_url' => $request->validated('link_url'),
            'topic_tag' => $request->filled('topic_tag') ? trim($request->string('topic_tag')->toString()) : null,
            'poll_options' => $request->pollOptions(),
        ];
    }

    /**
     * A JPEG copy, since Threads takes no WebP; at most 1440px wide, which
     * is the widest Threads shows.
     */
    private function storeImage(?UploadedFile $file): ?string
    {
        return $file ? $this->images->storeContents((string) $file->getContent(), 'threads', maxDimension: 1440, quality: 85, format: 'jpeg') : null;
    }

    /**
     * @return array<string, mixed>
     */
    private static function backInQueue(): array
    {
        return [
            'status' => ThreadsPostStatus::Scheduled,
            'attempts' => 0,
            'container_id' => null,
            'container_created_at' => null,
            'error' => null,
        ];
    }
}
