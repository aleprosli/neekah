<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Seo;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Seo $seo): View
    {
        $posts = Post::query()->published()->latest('published_at')->orderByDesc('id')->paginate(12);
        $page = $posts->currentPage();

        $seo->title($page > 1 ? __('seo.blog.title_page', ['page' => $page]) : __('seo.blog.title'))
            ->description(__('seo.blog.description'))
            ->canonical(route('blog.index', $page > 1 ? ['page' => $page] : []))
            ->breadcrumbs(['Neekah' => route('vendors.index'), 'Blog' => route('blog.index')]);

        $posts->setCollection($posts->getCollection()->map(fn (Post $post): array => $this->card($post)));

        return view('blog.index', [
            'props' => VueProps::for([
                'posts' => $posts->items(),
                'pagination' => $posts->hasPages() ? (string) $posts->links() : '',
            ]),
        ]);
    }

    /**
     * An admin can open a draft to preview it. Anyone else gets the same 404 as
     * for an address that never existed, so drafts stay unannounced.
     */
    public function show(Request $request, Post $post, Seo $seo): View
    {
        abort_unless($post->isPublished() || $request->user()?->isAdmin(), 404);

        $description = $post->meta_description ?: $post->summary();

        $seo->title($post->meta_title ?: $post->title)
            ->description($description)
            ->canonical($post->url())
            ->image($post->coverUrl())
            ->type('article')
            ->article($post->published_at, $post->updated_at)
            ->breadcrumbs(['Neekah' => route('vendors.index'), 'Blog' => route('blog.index'), $post->title => $post->url()])
            ->schema([
                '@type' => 'BlogPosting',
                'headline' => Str::limit($post->title, 110, ''),
                'description' => $description,
                'image' => $post->coverUrl() ? [$post->coverUrl()] : null,
                'datePublished' => $post->published_at?->toAtomString(),
                'dateModified' => $post->updated_at?->toAtomString(),
                'author' => ['@type' => 'Organization', 'name' => config('app.name'), 'url' => route('vendors.index')],
                'publisher' => [
                    '@type' => 'Organization',
                    'name' => config('app.name'),
                    'logo' => ['@type' => 'ImageObject', 'url' => asset(config('neekah.brand.mark'))],
                ],
                'mainEntityOfPage' => $post->url(),
            ]);

        if (! $post->isPublished()) {
            $seo->noindex();
        }

        return view('blog.show', [
            'post' => $post,
            'props' => VueProps::for([
                'blogUrl' => route('blog.index'),
                'post' => [
                    'title' => $post->title,
                    'draft' => ! $post->isPublished(),
                    'published' => $post->published_at ? $post->localPublishedAt()->translatedFormat('j F Y') : null,
                    'published_iso' => $post->published_at?->toAtomString(),
                    'reading' => $post->readingMinutes(),
                    'cover' => $post->cover_image ? $post->coverUrl() : null,
                    // Cleaned by App\Support\HtmlSanitizer when the article was saved.
                    'body' => $post->body,
                ],
                'related' => Post::query()->published()->whereKeyNot($post->getKey())->latest('published_at')->orderByDesc('id')->limit(3)->get()
                    ->map(fn (Post $other): array => [
                        'title' => $other->title,
                        'url' => $other->url(),
                        'cover' => $other->cover_image ? $other->coverThumbnailUrl() : null,
                    ])->values(),
            ]),
        ]);
    }

    /**
     * One article as the listing shows it.
     *
     * @return array<string, mixed>
     */
    private function card(Post $post): array
    {
        return [
            'title' => $post->title,
            'url' => $post->url(),
            'summary' => $post->summary(),
            'reading' => $post->readingMinutes(),
            'published' => $post->localPublishedAt()->translatedFormat('j F Y'),
            'published_iso' => $post->published_at->toAtomString(),
            'cover' => $post->cover_image ? $post->coverThumbnailUrl() : null,
        ];
    }
}
