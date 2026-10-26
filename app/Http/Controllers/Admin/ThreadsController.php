<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ConnectThreadsAccount;
use App\Actions\PlanThreadsPosts;
use App\Enums\ThreadsPostStatus;
use App\Enums\ThreadsPostType;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateThreadsSettingsRequest;
use App\Models\ThreadsPost;
use App\Models\ThreadsTemplate;
use App\Support\TableFilter;
use App\Support\Threads\ThreadsApiException;
use App\Support\Threads\ThreadsClient;
use App\Support\ThreadsSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Neekah's Threads account: connecting it, the daily plan, the queue of posts
 * on their way out, the template bank they are written from, and what went
 * out already. See .ai/rules/threads.md.
 */
class ThreadsController extends Controller
{
    private const OAUTH_STATE = 'threads_oauth_state';

    public function index(Request $request, ThreadsSettings $settings): View
    {
        $upcoming = ThreadsPost::query()
            ->with('vendor')
            ->where('scheduled_at', '>=', today())
            ->where('status', '!=', ThreadsPostStatus::Cancelled)
            ->orderBy('scheduled_at')
            ->limit(100)
            ->get();

        return view('admin.threads', [
            'props' => VueProps::for([
                'connection' => [
                    'configured' => ThreadsSettings::isConfigured(),
                    'connected' => $settings->isConnected(),
                    'expired' => $settings->userId() !== '' && $settings->tokenHasExpired(),
                    'username' => $settings->username(),
                    'profile_url' => $settings->username() !== '' ? 'https://www.threads.com/@'.$settings->username() : null,
                    'expires' => $settings->tokenExpiresAt()?->translatedFormat('j M Y'),
                    'redirect_uri' => ThreadsSettings::redirectUri(),
                    'connect_url' => route('admin.threads.connect'),
                    'disconnect_url' => route('admin.threads.disconnect'),
                ],
                'settings' => [
                    'enabled' => $settings->isSwitchedOn(),
                    'running' => $settings->isEnabled(),
                    'slots' => array_map(fn (array $slot): array => ['time' => $slot['time'], 'type' => $slot['type']->value], $settings->slots()),
                    'topic_tag' => $settings->topicTag(),
                    'days_ahead' => $settings->daysAhead(),
                    'listing_cooldown_days' => $settings->listingCooldownDays(),
                    'update_url' => route('admin.threads.settings'),
                ],
                'types' => collect(ThreadsPostType::cases())->map(fn (ThreadsPostType $type): array => [
                    'value' => $type->value,
                    'label' => $type->label(),
                    'description' => $type->description(),
                    'tone' => $type->tone(),
                    'placeholders' => $type->placeholders(),
                ])->values(),
                'stats' => [
                    ['label' => __('props.threads.stat_scheduled'), 'value' => ThreadsPost::scheduled()->count()],
                    ['label' => __('props.threads.stat_published_week'), 'value' => ThreadsPost::where('status', ThreadsPostStatus::Published)->where('published_at', '>=', now()->subDays(7))->count()],
                    ['label' => __('props.threads.stat_failed_week'), 'value' => ThreadsPost::whereIn('status', [ThreadsPostStatus::Failed, ThreadsPostStatus::Missed])->where('scheduled_at', '>=', now()->subDays(7))->count()],
                ],
                'upcoming' => $upcoming->map(fn (ThreadsPost $post): array => self::row($post))->values(),
                'templates' => ThreadsTemplate::query()->orderBy('type')->orderBy('id')->get()
                    ->map(fn (ThreadsTemplate $template): array => [
                        'id' => $template->id,
                        'type' => $template->type->value,
                        'type_label' => $template->type->label(),
                        'type_tone' => $template->type->tone(),
                        'body' => $template->body,
                        'topic_tag' => $template->topic_tag,
                        'poll_options' => $template->poll_options ?? [],
                        'is_active' => $template->is_active,
                        'used' => __('props.threads.used_times', ['count' => $template->used_count]),
                        'last_used' => $template->last_used_at?->diffForHumans(),
                        'update_url' => route('admin.threads.templates.update', $template),
                        'destroy_url' => route('admin.threads.templates.destroy', $template),
                    ])->values(),
                'limits' => [
                    'text_bytes' => ThreadsPost::MAX_TEXT_BYTES,
                    'poll_option' => ThreadsPost::MAX_POLL_OPTION_LENGTH,
                    'slots' => ThreadsSettings::MAX_SLOTS,
                    'days_ahead' => ThreadsSettings::MAX_DAYS_AHEAD,
                ],
                'urls' => [
                    'store_post' => route('admin.threads.posts.store'),
                    'store_template' => route('admin.threads.templates.store'),
                    'plan' => route('admin.threads.plan'),
                    'history' => route('admin.threads.data'),
                ],
                'statusFilter' => TableFilter::fromEnum('status', ThreadsPostStatus::cases(), $request->query('status'), label: __('props.admin.status')),
            ]),
        ]);
    }

    /**
     * Every post, newest first, for the history tab.
     */
    public function data(Request $request): JsonResponse
    {
        $statuses = TableFilter::requestedEnums($request, 'status', ThreadsPostStatus::class);
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $posts = ThreadsPost::query()
            ->with('vendor')
            ->when($request->string('search')->trim()->toString(), fn ($query, string $keyword) => $query->where('text', 'like', '%'.$keyword.'%'))
            ->when($statuses, fn ($query) => $query->whereIn('status', $statuses))
            ->orderBy('scheduled_at', $direction)
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $posts->getCollection()->map(fn (ThreadsPost $post): array => self::row($post))->all(),
            'meta' => [
                'total' => $posts->total(),
                'per_page' => $posts->perPage(),
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    /**
     * Send the admin to Threads to allow Neekah to post for the account they
     * are signed in to there.
     */
    public function connect(Request $request, ThreadsClient $client): RedirectResponse
    {
        if (! ThreadsSettings::isConfigured()) {
            return back()->withErrors(['threads' => __('flash.admin.threads_not_configured')]);
        }

        $state = Str::random(40);
        $request->session()->put(self::OAUTH_STATE, $state);

        return redirect()->away($client->authorizeUrl($state));
    }

    public function callback(Request $request, ConnectThreadsAccount $connect): RedirectResponse
    {
        $expected = (string) $request->session()->pull(self::OAUTH_STATE);
        $index = redirect()->route('admin.threads.index');

        // Someone pressed "Cancel" on Threads, or the link was not ours.
        if ($request->filled('error') || $expected === '' || ! hash_equals($expected, $request->string('state')->toString()) || ! $request->filled('code')) {
            return $index->withErrors(['threads' => __('flash.admin.threads_connect_cancelled')]);
        }

        try {
            $username = $connect->handle($request->string('code')->toString());
        } catch (ThreadsApiException $exception) {
            return $index->withErrors(['threads' => __('flash.admin.threads_connect_failed', ['reason' => $exception->getMessage()])]);
        }

        return $index->with('status', __('flash.admin.threads_connected', ['username' => $username]));
    }

    public function disconnect(ThreadsSettings $settings): RedirectResponse
    {
        $settings->forgetConnection();

        return back()->with('status', __('flash.admin.threads_disconnected'));
    }

    public function updateSettings(UpdateThreadsSettingsRequest $request, ThreadsSettings $settings, PlanThreadsPosts $plan): RedirectResponse
    {
        if ($request->boolean('enabled') && ! $settings->isConnected()) {
            return back()->withErrors(['enabled' => __('flash.admin.threads_not_connected')]);
        }

        $settings->save([
            'enabled' => $request->boolean('enabled'),
            'topic_tag' => trim((string) $request->validated('topic_tag')),
            'days_ahead' => (int) $request->validated('days_ahead'),
            'listing_cooldown_days' => (int) $request->validated('listing_cooldown_days'),
        ]);
        $settings->saveSlots($request->slots());

        // A new slot today should not wait for tonight's run to be filled.
        $planned = $settings->isEnabled() ? $plan->handle() : 0;

        return back()->with('status', __('flash.admin.threads_settings_saved', ['count' => $planned]));
    }

    /**
     * Fill the empty slots now. Works before the account is connected, so the
     * queue can be read through before anything goes out.
     */
    public function plan(PlanThreadsPosts $plan): RedirectResponse
    {
        return back()->with('status', __('flash.admin.threads_planned', ['count' => $plan->handle()]));
    }

    /**
     * @return array<string, mixed>
     */
    public static function row(ThreadsPost $post): array
    {
        $editable = $post->status->isEditable();

        return [
            'id' => $post->id,
            'type' => $post->type->value,
            'type_label' => $post->type->label(),
            'type_tone' => $post->type->tone(),
            'status' => $post->status->value,
            'status_label' => $post->status->label(),
            'status_tone' => $post->status->tone(),
            'text' => $post->text,
            'image' => $post->previewUrl(),
            'has_image' => $post->imageUrl() !== null,
            'link_url' => $post->link_url,
            'topic_tag' => $post->topic_tag,
            'poll_options' => array_pad($post->poll_options ?? [], 4, ''),
            'vendor' => $post->vendor?->name,
            'auto' => $post->slot_at !== null,
            'scheduled_at' => $post->scheduled_at->format('Y-m-d\TH:i'),
            'day' => $post->scheduled_at->toDateString(),
            'day_label' => $post->scheduled_at->translatedFormat('l, j F'),
            'time_label' => $post->scheduled_at->translatedFormat('g:i A'),
            'when' => $post->scheduled_at->translatedFormat('j M Y, g:i A'),
            'permalink' => $post->permalink,
            'error' => $post->error,
            'editable' => $editable,
            'update_url' => $editable ? route('admin.threads.posts.update', $post) : null,
            'cancel_url' => $editable ? route('admin.threads.posts.destroy', $post) : null,
            'publish_url' => $editable ? route('admin.threads.posts.publish', $post) : null,
            'rewrite_url' => $editable && $post->slot_at !== null ? route('admin.threads.posts.rewrite', $post) : null,
        ];
    }
}
