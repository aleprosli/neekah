<?php

namespace App\Http\Controllers\Admin;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Models\Post;
use App\Support\HtmlSanitizer;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
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
        return view('admin.posts.form', $this->formData(new Post));
    }

    /**
     * What the form component needs, with anything already typed put back.
     *
     * @return array<string, mixed>
     */
    private function formData(Post $post): array
    {
        $editing = $post->exists;

        return [
            'post' => $post,
            'isNew' => ! $editing,
            'props' => VueProps::for([
                'editing' => $editing,
                'action' => $editing ? route('admin.posts.update', $post) : route('admin.posts.store'),
                'imageUploadUrl' => route('admin.posts.images.store'),
                'post' => [
                    'title' => old('title', $post->title),
                    'body' => old('body', $post->body),
                    'excerpt' => old('excerpt', $post->excerpt),
                    'slug' => old('slug', $post->slug),
                    'meta_title' => old('meta_title', $post->meta_title),
                    'meta_description' => old('meta_description', $post->meta_description),
                    'status' => old('status', $post->published_at ? 'published' : 'draft'),
                    'published_at' => old('published_at', $post->localPublishedAt()?->format('Y-m-d\TH:i')),
                    'cover_url' => $post->cover_image ? $post->coverThumbnailUrl() : null,
                ],
            ]),
        ];
    }

    public function store(StorePostRequest $request, HtmlSanitizer $sanitizer, StoreOptimizedImage $storeImage): RedirectResponse|JsonResponse
    {
        $post = new Post($request->attributesForPost($sanitizer));
        $post->user_id = $request->user()->id;

        if ($request->hasFile('cover_image')) {
            $post->cover_image = $storeImage->handle($request->file('cover_image'), 'blog/covers');
        }

        $post->save();

        return $this->redirectOrJson($request, route('admin.posts.edit', $post), $this->savedMessage($post));
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', $this->formData($post));
    }

    public function update(StorePostRequest $request, Post $post, HtmlSanitizer $sanitizer, StoreOptimizedImage $storeImage): RedirectResponse|JsonResponse
    {
        $attributes = $request->attributesForPost($sanitizer);

        if ($request->hasFile('cover_image')) {
            $storeImage->delete($post->cover_image);
            $attributes['cover_image'] = $storeImage->handle($request->file('cover_image'), 'blog/covers');
        }

        $post->update($attributes);

        return $this->redirectOrJson($request, route('admin.posts.edit', $post), $this->savedMessage($post));
    }

    public function destroy(Post $post, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $storeImage->delete($post->cover_image);
        $post->delete();

        return redirect()->route('admin.posts.index')->with('status', __('flash.admin.post_deleted'));
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
