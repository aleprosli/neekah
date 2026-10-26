<?php

use App\Enums\ThreadsPostStatus;
use App\Enums\ThreadsPostType;
use App\Jobs\SendTelegramAlert;
use App\Models\Post;
use App\Models\Setting;
use App\Models\ThreadsPost;
use App\Models\ThreadsTemplate;
use App\Models\User;
use App\Models\Vendor;
use App\Support\TelegramSettings;
use App\Support\ThreadsSettings;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

const GRAPH = 'https://graph.threads.com';

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    config(['services.threads.app_id' => '123456', 'services.threads.app_secret' => 'app-secret']);
    Http::preventStrayRequests();
});

/** A connected account with auto-posting switched on. */
function connectThreads(int $expiresInDays = 60): ThreadsSettings
{
    $settings = app(ThreadsSettings::class);
    $settings->saveConnection('1789', 'neekah.my', 'long-token', $expiresInDays * 86400);
    $settings->save(['enabled' => true]);

    return $settings;
}

function vendorWithCover(array $attributes = []): Vendor
{
    Storage::disk('public')->put('vendors/cover.webp', UploadedFile::fake()->image('cover.jpg', 1600, 900)->getContent());

    return Vendor::factory()->create(['cover_image' => 'vendors/cover.webp', ...$attributes]);
}

it('is for admins only', function () {
    $this->actingAs(User::factory()->vendor()->create())
        ->get(route('admin.threads.index'))
        ->assertForbidden();
});

it('shows the page before the account is connected', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.threads.index'))
        ->assertOk()
        ->assertSee('data-vue="admin-threads-page"', false)
        ->assertSee(ThreadsSettings::redirectUri(), false);
});

describe('connecting', function () {
    it('sends the admin to Threads with the scopes and a state to check', function () {
        $response = $this->actingAs($this->admin)->get(route('admin.threads.connect'));

        $location = $response->assertRedirect()->headers->get('Location');
        parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

        expect($location)->toStartWith('https://threads.com/oauth/authorize?')
            ->and($query['client_id'])->toBe('123456')
            ->and($query['scope'])->toBe('threads_basic,threads_content_publish,threads_manage_insights')
            ->and($query['redirect_uri'])->toBe(ThreadsSettings::redirectUri())
            ->and($query['state'])->toBe(session('threads_oauth_state'));
    });

    it('refuses to start while the Meta app is not in .env', function () {
        config(['services.threads.app_secret' => null]);

        $this->actingAs($this->admin)
            ->from(route('admin.threads.index'))
            ->get(route('admin.threads.connect'))
            ->assertRedirect(route('admin.threads.index'))
            ->assertSessionHasErrors(['threads' => __('flash.admin.threads_not_configured')]);
    });

    it('swaps the code for a 60-day token and keeps it encrypted', function () {
        Http::fake([
            GRAPH.'/oauth/access_token' => Http::response(['access_token' => 'short-token', 'user_id' => '1789']),
            GRAPH.'/access_token*' => Http::response(['access_token' => 'long-token', 'token_type' => 'bearer', 'expires_in' => 5184000]),
            GRAPH.'/v1.0/me*' => Http::response(['id' => '1789', 'username' => 'neekah.my']),
        ]);

        $this->actingAs($this->admin)
            ->withSession(['threads_oauth_state' => 'state-123'])
            ->get(route('admin.threads.callback', ['code' => 'the-code', 'state' => 'state-123']))
            ->assertRedirect(route('admin.threads.index'))
            ->assertSessionHas('status', __('flash.admin.threads_connected', ['username' => 'neekah.my']));

        $settings = app(ThreadsSettings::class);

        expect($settings->isConnected())->toBeTrue()
            ->and($settings->username())->toBe('neekah.my')
            ->and($settings->accessToken())->toBe('long-token')
            ->and(Setting::find('threads.access_token')->value)->not->toContain('long-token')
            ->and($settings->tokenExpiresAt()->isSameDay(now()->addDays(60)))->toBeTrue();

        Http::assertSent(fn (Request $request) => $request->url() === GRAPH.'/oauth/access_token'
            && $request['code'] === 'the-code'
            && $request['redirect_uri'] === ThreadsSettings::redirectUri());
    });

    it('ignores a callback whose state is not the one it sent', function () {
        $this->actingAs($this->admin)
            ->withSession(['threads_oauth_state' => 'state-123'])
            ->get(route('admin.threads.callback', ['code' => 'the-code', 'state' => 'forged']))
            ->assertSessionHasErrors(['threads' => __('flash.admin.threads_connect_cancelled')]);

        expect(app(ThreadsSettings::class)->isConnected())->toBeFalse();
        Http::assertNothingSent();
    });
});

describe('planning', function () {
    beforeEach(function () {
        Storage::fake('public', ['url' => 'https://cdn.neekah.my']);
        $this->travelTo(now()->setDate(2026, 11, 2)->setTime(9, 0));
        app(ThreadsSettings::class)->save(['days_ahead' => 1]);
        app(ThreadsSettings::class)->saveSlots([
            ['time' => '10:30', 'type' => 'listing'],
            ['time' => '12:45', 'type' => 'engagement'],
        ]);
    });

    it('fills each slot from a vendor and from the template bank', function () {
        $vendor = vendorWithCover(['name' => 'Studio Seri']);
        ThreadsTemplate::factory()->listing()->create();
        $poll = ThreadsTemplate::factory()->poll()->create();

        $this->actingAs($this->admin)
            ->post(route('admin.threads.plan'))
            ->assertSessionHas('status', __('flash.admin.threads_planned', ['count' => 2]));

        [$listing, $engagement] = ThreadsPost::orderBy('scheduled_at')->get();

        expect($listing->type)->toBe(ThreadsPostType::Listing)
            ->and($listing->vendor_id)->toBe($vendor->id)
            ->and($listing->scheduled_at->format('H:i'))->toBe('10:30')
            ->and($listing->text)->toContain('Studio Seri')
            ->and($listing->text)->toContain('utm_source=threads')
            ->and($listing->image_path)->toEndWith('.jpg')
            ->and($engagement->type)->toBe(ThreadsPostType::Engagement)
            ->and($engagement->text)->toBe($poll->body)
            ->and($engagement->pollAttachment())->toBe(['option_a' => 'Siang', 'option_b' => 'Malam'])
            ->and($poll->fresh()->used_count)->toBe(1);

        Storage::disk('public')->assertExists($listing->image_path);
    });

    it('never fills a slot twice, even after the admin cancelled its post', function () {
        vendorWithCover();
        ThreadsTemplate::factory()->poll()->create();

        $this->artisan('neekah:threads-plan');
        connectThreads();
        $this->artisan('neekah:threads-plan');
        ThreadsPost::query()->update(['status' => ThreadsPostStatus::Cancelled]);
        $this->artisan('neekah:threads-plan');

        expect(ThreadsPost::count())->toBe(2);
    });

    it('rests a vendor who was featured recently', function () {
        $featured = vendorWithCover();
        ThreadsPost::factory()->published()->create(['vendor_id' => $featured->id, 'scheduled_at' => now()->subDays(10)]);
        $waiting = vendorWithCover();

        $this->actingAs($this->admin)->post(route('admin.threads.plan'));

        // The engagement slot falls back to a listing too, and finds nobody left to feature.
        expect(ThreadsPost::whereNull('threads_media_id')->pluck('vendor_id')->all())->toBe([$waiting->id]);
    });

    it('posts a vendor instead when a type has nothing to write from', function () {
        app(ThreadsSettings::class)->saveSlots([['time' => '18:00', 'type' => 'blog']]);
        $vendor = vendorWithCover();

        $this->actingAs($this->admin)->post(route('admin.threads.plan'));

        expect(ThreadsPost::sole())->type->toBe(ThreadsPostType::Listing)->vendor_id->toBe($vendor->id);
    });

    it('shares a published blog post as a text post with its link', function () {
        app(ThreadsSettings::class)->saveSlots([['time' => '18:00', 'type' => 'blog']]);
        $article = Post::factory()->published()->create(['title' => 'Bajet kahwin RM20k']);

        $this->actingAs($this->admin)->post(route('admin.threads.plan'));

        expect(ThreadsPost::sole())
            ->post_id->toBe($article->id)
            ->text->toContain('Bajet kahwin RM20k')
            ->image_path->toBeNull()
            ->link_url->toStartWith($article->url().'?utm_source=threads');
    });

    it('is not run by the scheduler while auto-posting is off', function () {
        vendorWithCover();

        $this->artisan('neekah:threads-plan')->assertSuccessful();

        expect(ThreadsPost::count())->toBe(0);
    });
});

describe('publishing', function () {
    it('does nothing while auto-posting is off', function () {
        ThreadsPost::factory()->due()->create();

        $this->artisan('neekah:threads-publish')->assertSuccessful();

        Http::assertNothingSent();
        expect(ThreadsPost::sole()->status)->toBe(ThreadsPostStatus::Scheduled);
    });

    it('creates the container for a due text post with its link and topic', function () {
        connectThreads();
        $post = ThreadsPost::factory()->due()->create(['link_url' => 'https://neekah.my/vendors/seri', 'topic_tag' => 'Kahwin.MY & co']);
        Http::fake([GRAPH.'/v1.0/1789/threads' => Http::response(['id' => 'container-1'])]);

        $this->artisan('neekah:threads-publish')->assertSuccessful();

        Http::assertSent(fn (Request $request) => $request['media_type'] === 'TEXT'
            && $request['text'] === $post->text
            && $request['link_attachment'] === 'https://neekah.my/vendors/seri'
            && $request['topic_tag'] === 'KahwinMY  co'
            && $request['access_token'] === 'long-token');

        expect($post->fresh())->status->toBe(ThreadsPostStatus::Processing)->container_id->toBe('container-1');
    });

    it('sends an image post with the JPEG address and no link card', function () {
        Storage::fake('public', ['url' => 'https://cdn.neekah.my']);
        connectThreads();
        ThreadsPost::factory()->due()->create(['image_path' => 'threads/a.jpg', 'link_url' => 'https://neekah.my/x', 'poll_options' => ['Ya', 'Tidak']]);
        Http::fake([GRAPH.'/v1.0/1789/threads' => Http::response(['id' => 'container-2'])]);

        $this->artisan('neekah:threads-publish');

        Http::assertSent(fn (Request $request) => $request['media_type'] === 'IMAGE'
            && $request['image_url'] === 'https://cdn.neekah.my/threads/a.jpg'
            && ! isset($request['link_attachment'])
            && ! isset($request['poll_attachment']));
    });

    it('publishes a finished container and keeps the permalink', function () {
        connectThreads();
        $post = ThreadsPost::factory()->processing()->create();
        Http::fake([
            GRAPH.'/v1.0/17890000000000001?*' => Http::response(['status' => 'FINISHED', 'id' => '17890000000000001']),
            GRAPH.'/v1.0/1789/threads_publish' => Http::response(['id' => 'media-9']),
            GRAPH.'/v1.0/media-9?*' => Http::response(['permalink' => 'https://www.threads.com/@neekah.my/post/xyz']),
        ]);

        $this->artisan('neekah:threads-publish');

        expect($post->fresh())
            ->status->toBe(ThreadsPostStatus::Published)
            ->threads_media_id->toBe('media-9')
            ->permalink->toBe('https://www.threads.com/@neekah.my/post/xyz')
            ->published_at->not->toBeNull();

        Http::assertSent(fn (Request $request) => str_ends_with($request->url(), '/threads_publish') && $request['creation_id'] === '17890000000000001');
    });

    it('fails a post Threads could not process and tells the admin chat', function () {
        Queue::fake([SendTelegramAlert::class]);
        app(TelegramSettings::class)->save(['enabled' => true, 'bot_token' => 'bot', 'chat_id' => '42']);
        connectThreads();
        $post = ThreadsPost::factory()->processing()->create();
        Http::fake([GRAPH.'/v1.0/17890000000000001?*' => Http::response(['status' => 'ERROR', 'error_message' => 'FAILED_DOWNLOADING_IMAGE'])]);

        $this->artisan('neekah:threads-publish');

        expect($post->fresh())->status->toBe(ThreadsPostStatus::Failed)->error->toBe('FAILED_DOWNLOADING_IMAGE');
        Queue::assertPushed(SendTelegramAlert::class);
    });

    it('tries again later when Threads is down, and gives up after three tries', function () {
        connectThreads();
        $post = ThreadsPost::factory()->due()->create();
        Http::fake([GRAPH.'/v1.0/1789/threads' => Http::response(['error' => ['message' => 'Service unavailable']], 503)]);

        $this->artisan('neekah:threads-publish');
        expect($post->fresh())->status->toBe(ThreadsPostStatus::Scheduled)->attempts->toBe(1);

        $this->artisan('neekah:threads-publish');
        $this->artisan('neekah:threads-publish');
        expect($post->fresh())->status->toBe(ThreadsPostStatus::Failed)->error->toBe('Service unavailable');
    });

    it('fails at once when Threads refuses the post itself', function () {
        connectThreads();
        $post = ThreadsPost::factory()->due()->create();
        Http::fake([GRAPH.'/v1.0/1789/threads' => Http::response(['error' => ['message' => 'Invalid parameter', 'error_user_msg' => 'Teks terlalu panjang']], 400)]);

        $this->artisan('neekah:threads-publish');

        expect($post->fresh())->status->toBe(ThreadsPostStatus::Failed)->error->toBe('Teks terlalu panjang');
    });

    it('marks a post missed rather than sending it hours late', function () {
        connectThreads();
        $post = ThreadsPost::factory()->create(['scheduled_at' => now()->subHours(3)]);

        $this->artisan('neekah:threads-publish');

        expect($post->fresh()->status)->toBe(ThreadsPostStatus::Missed);
        Http::assertNothingSent();
    });

    it('starts one new post a minute, so posts due together do not go out at once', function () {
        connectThreads();
        ThreadsPost::factory()->due()->count(2)->create();
        Http::fake([GRAPH.'/v1.0/1789/threads' => Http::response(['id' => 'container-1'])]);

        $this->artisan('neekah:threads-publish');

        expect(ThreadsPost::where('status', ThreadsPostStatus::Processing)->count())->toBe(1);
    });
});

describe('the token', function () {
    it('is refreshed once it has under 30 days left', function () {
        $settings = connectThreads(expiresInDays: 20);
        $this->travel(2)->days();
        Http::fake([GRAPH.'/refresh_access_token*' => Http::response(['access_token' => 'new-token', 'expires_in' => 5184000])]);

        $this->artisan('neekah:threads-token')->assertSuccessful();

        expect($settings->accessToken())->toBe('new-token')
            ->and($settings->tokenExpiresAt()->isSameDay(now()->addDays(60)))->toBeTrue();
    });

    it('is left alone while it has weeks to go', function () {
        connectThreads(expiresInDays: 50);
        $this->travel(2)->days();

        $this->artisan('neekah:threads-token')->assertSuccessful();

        Http::assertNothingSent();
    });
});

describe('the queue', function () {
    it('takes a post written by hand, with a poll', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.threads.posts.store'), [
                'type' => 'engagement',
                'text' => 'Siang atau malam?',
                'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'poll_options' => ['Siang', 'Malam', '', ''],
            ])
            ->assertSessionHasNoErrors();

        expect(ThreadsPost::sole())
            ->status->toBe(ThreadsPostStatus::Scheduled)
            ->user_id->toBe($this->admin->id)
            ->slot_at->toBeNull()
            ->poll_options->toBe(['Siang', 'Malam']);
    });

    it('stores an uploaded image as a JPEG, since Threads takes no WebP', function () {
        Storage::fake('public');

        $this->actingAs($this->admin)->post(route('admin.threads.posts.store'), [
            'type' => 'promo',
            'text' => 'Kad digital',
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'image' => UploadedFile::fake()->image('kad.png', 2000, 1000),
        ]);

        expect(ThreadsPost::sole()->image_path)->toEndWith('.jpg');
    });

    it('refuses text over the Threads limit, counted in bytes', function () {
        $this->actingAs($this->admin)
            ->post(route('admin.threads.posts.store'), [
                'type' => 'tip',
                'text' => str_repeat('💍', 126),
                'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHasErrors(['text' => __('validation.custom.threads_text_too_long', ['max' => 500])]);
    });

    it('cancels a post without deleting it, and cannot touch one already out', function () {
        $post = ThreadsPost::factory()->create();
        $published = ThreadsPost::factory()->published()->create();

        $this->actingAs($this->admin)->delete(route('admin.threads.posts.destroy', $post));
        $this->actingAs($this->admin)->delete(route('admin.threads.posts.destroy', $published))->assertForbidden();

        expect($post->fresh()->status)->toBe(ThreadsPostStatus::Cancelled)
            ->and($published->fresh()->status)->toBe(ThreadsPostStatus::Published);
    });

    it('puts a failed post back in the queue to go out now', function () {
        $post = ThreadsPost::factory()->create(['status' => ThreadsPostStatus::Failed, 'error' => 'x', 'attempts' => 3, 'scheduled_at' => now()->subHour()]);

        $this->actingAs($this->admin)->post(route('admin.threads.posts.publish', $post));

        expect($post->fresh())
            ->status->toBe(ThreadsPostStatus::Scheduled)
            ->attempts->toBe(0)
            ->error->toBeNull()
            ->scheduled_at->lte(now())->toBeTrue();
    });

    it('rewrites a planned post from the next template in turn', function () {
        [$first, $second] = ThreadsTemplate::factory()->count(2)->create(['type' => ThreadsPostType::Tip]);
        $first->markUsed();
        $post = ThreadsPost::factory()->create(['type' => ThreadsPostType::Tip, 'text' => $first->body, 'threads_template_id' => $first->id, 'slot_at' => now()->addHour()]);

        $this->actingAs($this->admin)->post(route('admin.threads.posts.rewrite', $post))->assertSessionHasNoErrors();

        expect($post->fresh())->text->toBe($second->body)->threads_template_id->toBe($second->id);
    });
});

describe('settings', function () {
    it('will not switch on before the account is connected', function () {
        $this->actingAs($this->admin)
            ->put(route('admin.threads.settings'), [
                'enabled' => '1',
                'days_ahead' => 2,
                'listing_cooldown_days' => 30,
                'slots' => [['time' => '09:00', 'type' => 'tip']],
            ])
            ->assertSessionHasErrors(['enabled' => __('flash.admin.threads_not_connected')]);

        expect(app(ThreadsSettings::class)->isSwitchedOn())->toBeFalse();
    });

    it('saves the daily plan in time order', function () {
        $this->actingAs($this->admin)
            ->put(route('admin.threads.settings'), [
                'enabled' => '0',
                'topic_tag' => 'Kahwin',
                'days_ahead' => 3,
                'listing_cooldown_days' => 14,
                'slots' => [['time' => '21:00', 'type' => 'engagement'], ['time' => '09:00', 'type' => 'listing']],
            ])
            ->assertSessionHasNoErrors();

        $settings = app(ThreadsSettings::class);

        expect($settings->slots())->toBe([
            ['time' => '09:00', 'type' => ThreadsPostType::Listing],
            ['time' => '21:00', 'type' => ThreadsPostType::Engagement],
        ])->and($settings->daysAhead())->toBe(3)
            ->and($settings->topicTag())->toBe('Kahwin');
    });

    it('refuses two slots at the same minute', function () {
        $this->actingAs($this->admin)
            ->put(route('admin.threads.settings'), [
                'days_ahead' => 2,
                'listing_cooldown_days' => 30,
                'slots' => [['time' => '09:00', 'type' => 'tip'], ['time' => '09:00', 'type' => 'listing']],
            ])
            ->assertSessionHasErrors('slots.0.time');
    });
});

it('keeps a template bank with polls', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.threads.templates.store'), [
            'type' => 'engagement',
            'body' => 'Dewan atau rumah?',
            'poll_options' => ['Dewan', 'Rumah', ''],
            'is_active' => '1',
        ])
        ->assertSessionHasNoErrors();

    expect(ThreadsTemplate::sole())->poll_options->toBe(['Dewan', 'Rumah'])->is_active->toBeTrue();
});
