<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PostController extends Controller
{
    public function index(): View
    {
        $posts = Post::query()->latest('updated_at')->orderByDesc('id')->paginate(20);

        $posts->setCollection($posts->getCollection()->map(fn (Post $post): array => [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'state' => match (true) {
                $post->isPublished() => 'published',
                $post->isScheduled() => 'scheduled',
                default => 'draft',
            },
            'status_label' => match (true) {
                $post->isPublished() => 'Tersiar',
                $post->isScheduled() => 'Dijadualkan '.$post->localPublishedAt()->translatedFormat('j M, g:i A'),
                default => 'Draf',
            },
            'updated' => $post->updated_at->diffForHumans(),
            'edit_url' => route('admin.posts.edit', $post),
            'destroy_url' => route('admin.posts.destroy', $post),
            'public_url' => $post->url(),
        ]));

        return view('admin.posts.index', [
            'posts' => $posts,
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.form', ['post' => new Post]);
    }

    public function store(StorePostRequest $request, HtmlSanitizer $sanitizer, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $post = new Post($request->attributesForPost($sanitizer));
        $post->user_id = $request->user()->id;

        if ($request->hasFile('cover_image')) {
            $post->cover_image = $storeImage->handle($request->file('cover_image'), 'blog/covers');
        }

        $post->save();

        return redirect()->route('admin.posts.edit', $post)->with('status', $this->savedMessage($post));
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', ['post' => $post]);
    }

    public function update(StorePostRequest $request, Post $post, HtmlSanitizer $sanitizer, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $attributes = $request->attributesForPost($sanitizer);

        if ($request->hasFile('cover_image')) {
            $storeImage->delete($post->cover_image);
            $attributes['cover_image'] = $storeImage->handle($request->file('cover_image'), 'blog/covers');
        }

        $post->update($attributes);

        return redirect()->route('admin.posts.edit', $post)->with('status', $this->savedMessage($post));
    }

    public function destroy(Post $post, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $storeImage->delete($post->cover_image);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', 'Artikel dipadam.');
    }

    private function savedMessage(Post $post): string
    {
        return match (true) {
            $post->isPublished() => 'Artikel tersiar di '.$post->url(),
            $post->isScheduled() => 'Artikel dijadualkan tersiar pada '.$post->localPublishedAt()->translatedFormat('j F Y, g:i A').'.',
            default => 'Draf disimpan. Pilih "Siarkan" apabila artikel sudah sedia.',
        };
    }
}
