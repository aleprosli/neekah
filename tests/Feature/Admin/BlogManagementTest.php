<?php

use App\Actions\StoreOptimizedImage;
use App\Models\Post;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

it('publishes an article under a slug made from its title', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->post(route('admin.posts.store'), [
            'title' => 'Tips Memilih Pelamin Idaman',
            'body' => '<h2>Mula dengan bajet</h2><p>Tetapkan bajet dahulu.</p>',
            'status' => 'published',
        ])
        ->assertRedirect();

    $post = Post::sole();

    expect($post->slug)->toBe('tips-memilih-pelamin-idaman')
        ->and($post->isPublished())->toBeTrue()
        ->and($post->user_id)->toBe($admin->id);
});

it('keeps a draft off the published list', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), [
            'title' => 'Masih Ditulis',
            'body' => '<p>Belum siap.</p>',
            'status' => 'draft',
        ]);

    expect(Post::sole()->published_at)->toBeNull();
});

it('reads a chosen publish time as malaysian time', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), [
            'title' => 'Artikel Berjadual',
            'body' => '<p>Isi.</p>',
            'status' => 'published',
            'published_at' => '2030-10-01T09:00',
        ]);

    $post = Post::sole();

    expect($post->published_at->equalTo(Carbon::parse('2030-10-01 01:00:00', 'UTC')))->toBeTrue()
        ->and($post->isScheduled())->toBeTrue();
});

it('keeps the original publish date when a published article is edited', function () {
    $post = Post::factory()->published()->create();
    $originalDate = $post->published_at;

    $this->actingAs(User::factory()->admin()->create())
        ->put(route('admin.posts.update', $post), [
            'title' => $post->title,
            'slug' => $post->slug,
            'body' => '<p>Ejaan dibetulkan.</p>',
            'status' => 'published',
        ]);

    expect($post->fresh()->published_at->equalTo($originalDate))->toBeTrue();
});

it('strips scripts, event handlers and unsafe links from the article body', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), [
            'title' => 'Artikel Bersih',
            'body' => '<p onclick="alert(1)">Hai <a href="javascript:alert(1)">klik</a></p>'
                .'<script>alert(1)</script>'
                .'<img src="x" onerror="alert(1)">'
                .'<img src="https://neekah.my/storage/blog/a.webp" alt="Pelamin">',
            'status' => 'draft',
        ]);

    $body = Post::sole()->body;

    expect($body)->toContain('<p>Hai <a>klik</a></p>')
        ->toContain('<img src="https://neekah.my/storage/blog/a.webp" alt="Pelamin" loading="lazy" decoding="async">')
        ->not->toContain('script')
        ->not->toContain('onclick')
        ->not->toContain('onerror')
        ->not->toContain('javascript:');
});

it('refuses a slug another article already uses', function () {
    Post::factory()->create(['slug' => 'tips-pelamin']);

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), [
            'title' => 'Tips Pelamin',
            'body' => '<p>Isi.</p>',
            'status' => 'draft',
        ])
        ->assertSessionHasErrors('slug');

    expect(Post::count())->toBe(1);
});

it('stores the cover image resized, as webp, with a thumbnail', function () {
    Storage::fake('public');

    $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.store'), [
            'title' => 'Artikel Bergambar',
            'body' => '<p>Isi.</p>',
            'status' => 'draft',
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 3000, 2000),
        ]);

    $cover = Post::sole()->cover_image;

    expect($cover)->toEndWith('.webp')
        ->and(getimagesizefromstring(Storage::disk('public')->get($cover))[0])->toBe(1920);
    Storage::disk('public')->assertExists(StoreOptimizedImage::thumbnailPath($cover));
});

it('deletes an article together with its cover image', function () {
    Storage::fake('public');
    Storage::disk('public')->put('blog/covers/a.webp', 'x');
    Storage::disk('public')->put('blog/covers/a-thumb.webp', 'x');
    $post = Post::factory()->create(['cover_image' => 'blog/covers/a.webp']);

    $this->actingAs(User::factory()->admin()->create())
        ->delete(route('admin.posts.destroy', $post))
        ->assertRedirect(route('admin.posts.index'));

    $this->assertModelMissing($post);
    Storage::disk('public')->assertMissing(['blog/covers/a.webp', 'blog/covers/a-thumb.webp']);
});

it('uploads an image dropped into the editor and returns its address', function () {
    Storage::fake('public');

    $url = $this->actingAs(User::factory()->admin()->create())
        ->post(route('admin.posts.images.store'), ['image' => UploadedFile::fake()->image('pelamin.png', 800, 600)])
        ->assertCreated()
        ->json('url');

    expect($url)->toEndWith('.webp');
});

it('keeps the blog admin away from anyone who is not an admin', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.posts.index'))
        ->assertForbidden();
});
