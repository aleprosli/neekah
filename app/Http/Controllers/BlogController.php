<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Seo $seo): View
    {
        $posts = Post::query()->published()->latest('published_at')->orderByDesc('id')->paginate(12);
        $page = $posts->currentPage();

        $seo->title('Blog perkahwinan'.($page > 1 ? ' — halaman '.$page : ''))
            ->description('Tip, idea dan panduan merancang majlis perkahwinan di Malaysia: bajet, vendor, adat, kad jemputan dan banyak lagi.')
            ->canonical(route('blog.index', $page > 1 ? ['page' => $page] : []))
            ->breadcrumbs(['Neekah' => route('vendors.index'), 'Blog' => route('blog.index')]);

        return view('blog.index', ['posts' => $posts]);
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
            'related' => Post::query()->published()->whereKeyNot($post->getKey())->latest('published_at')->orderByDesc('id')->limit(3)->get(),
        ]);
    }
}
