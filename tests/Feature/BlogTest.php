<?php

use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Str;

it('lists only published articles, newest first', function () {
    Post::factory()->published()->create(['title' => 'Artikel Lama', 'published_at' => now()->subWeek()]);
    Post::factory()->published()->create(['title' => 'Artikel Baru']);
    Post::factory()->create(['title' => 'Draf Rahsia']);
    Post::factory()->scheduled()->create(['title' => 'Belum Tiba Masa']);

    $this->get(route('blog.index'))
        ->assertOk()
        ->assertSeeInOrder(['Artikel Baru', 'Artikel Lama'])
        ->assertDontSee('Draf Rahsia')
        ->assertDontSee('Belum Tiba Masa');
});

it('answers a draft with the same 404 as a missing page', function () {
    $draft = Post::factory()->create();

    $this->get(route('blog.show', $draft))->assertNotFound();
});

it('lets an admin preview a draft without it being indexed', function () {
    $draft = Post::factory()->create(['title' => 'Draf Untuk Disemak']);

    $this->actingAs(User::factory()->admin()->create())
        ->get(route('blog.show', $draft))
        ->assertOk()
        ->assertSee('Draf Untuk Disemak')
        ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
        ->assertDontSee('application/ld+json', false);
});

it('describes an article to search engines', function () {
    $post = Post::factory()->published()->create([
        'title' => 'Panduan Bajet Kahwin',
        'meta_description' => 'Cara pecahkan bajet kahwin ikut kategori.',
    ]);

    $response = $this->get($post->url())
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$post->url().'">', false)
        ->assertSee('<meta name="description" content="Cara pecahkan bajet kahwin ikut kategori.">', false)
        ->assertSee('<meta property="og:type" content="article">', false)
        ->assertSee('<meta property="article:published_time" content="'.$post->published_at->toAtomString().'">', false);

    $graph = collect(json_decode(Str::betweenFirst($response->getContent(), '<script type="application/ld+json">', '</script>'), true)['@graph']);

    expect($graph->pluck('@type')->all())->toContain('BlogPosting', 'BreadcrumbList')
        ->and($graph->firstWhere('@type', 'BlogPosting')['headline'])->toBe('Panduan Bajet Kahwin');
});

it('escapes an article opening once when it stands in for a missing summary', function () {
    $post = Post::factory()->published()->create([
        'excerpt' => null,
        'meta_description' => null,
        'body' => '<p>Bajet &amp; tetamu&nbsp;&lt;script&gt;alert(1)&lt;/script&gt;</p>',
    ]);

    expect($post->summary())->toBe('Bajet & tetamu <script>alert(1)</script>');

    $this->get($post->url())
        ->assertOk()
        ->assertSee('<meta name="description" content="Bajet &amp; tetamu &lt;script&gt;alert(1)&lt;/script&gt;">', false);
});

it('lists published articles in the sitemap and leaves drafts out', function () {
    $published = Post::factory()->published()->create();
    $draft = Post::factory()->create();

    $this->get(route('sitemap.blog'))
        ->assertOk()
        ->assertSee($published->url())
        ->assertDontSee($draft->url());

    $this->get(route('sitemap.index'))->assertSee(route('sitemap.blog'));
});
