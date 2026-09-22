<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Support\AnnouncementPresets;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnnouncementController extends Controller
{
    /**
     * Write something and tell every couple, every vendor, both, or a list
     * picked by hand. The page carries the live recipient count per audience,
     * because "send to everyone" should say how many people that is before it
     * is pressed.
     */
    public function index(): View
    {
        $announcements = Announcement::with('author')->withCount('users')->latest()->limit(50)->get();

        return view('admin.announcements.index', [
            'props' => VueProps::for([
                'storeUrl' => route('admin.announcements.store'),
                // A starting point for the messages that get written over and over.
                'presets' => AnnouncementPresets::all(),
                'testUrl' => route('admin.announcements.test'),
                'searchUrl' => route('admin.announcements.recipients'),
                'audiences' => collect(AnnouncementAudience::cases())
                    ->map(fn (AnnouncementAudience $audience): array => [
                        'value' => $audience->value,
                        'label' => $audience->label(),
                        'description' => $audience->description(),
                        'custom' => $audience->isCustom(),
                        'count' => $audience->isCustom() ? null : $audience->recipients()->count(),
                    ])->values(),
                'announcements' => $announcements->map(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'subject' => $announcement->subject,
                    'audience' => $announcement->audience->label(),
                    'recipients' => $announcement->status === AnnouncementStatus::Sent
                        ? __('props.units.recipients', ['count' => $announcement->recipients_count])
                        : '—',
                    'sent_at' => $announcement->sent_at?->translatedFormat('j M Y, g:i A') ?? '—',
                    'author' => $announcement->author?->name ?? 'Admin',
                    'status_label' => $announcement->status->label(),
                    'status_tone' => $announcement->status->tone(),
                    'url' => route('admin.announcements.show', $announcement),
                ])->values(),
                'notice' => session('status'),
            ]),
        ]);
    }

    /**
     * Accounts matching what an admin is typing into the recipient picker. The
     * same exclusions as every other audience apply: no admins, nobody
     * deactivated, so a list picked by hand cannot reach someone a broad
     * audience would have left out.
     */
    public function recipients(Request $request): JsonResponse
    {
        $keyword = $request->string('search')->trim()->toString();

        $users = AnnouncementAudience::Everyone->recipients()
            ->when($keyword, function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('email', 'like', $like));
            })
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email', 'role']);

        return response()->json([
            'data' => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->label(),
            ])->values(),
        ]);
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $custom = $request->chosenAudience() === AnnouncementAudience::Custom;
        $addresses = $custom ? $request->typedAddresses() : collect();

        // An address that belongs to an account is that account, so they get
        // the notification bell too rather than a bare email.
        $accounts = User::whereIn(DB::raw('lower(email)'), $addresses->all())->get(['id', 'email']);
        $strangers = $addresses->diff($accounts->pluck('email')->map(fn (string $email): string => Str::lower($email)));

        $announcement = Announcement::create([
            ...$request->safe()->only(['audience', 'subject', 'body', 'action_label', 'action_url']),
            'custom_emails' => $strangers->values()->all(),
            'user_id' => $request->user()->id,
            'status' => AnnouncementStatus::Draft,
        ]);

        if ($custom) {
            $announcement->users()->sync(collect($request->input('user_ids', []))->merge($accounts->pluck('id'))->unique()->all());
        }

        SendAnnouncement::dispatch($announcement);

        return redirect()
            ->route('admin.announcements.index')
            ->with('status', __('flash.admin.announcement_sent', ['audience' => $this->audienceSummary($announcement)]));
    }

    public function show(Announcement $announcement): View
    {
        $announcement->load('author', 'users');
        $sent = $announcement->status === AnnouncementStatus::Sent;

        return view('admin.announcements.show', [
            'announcement' => $announcement,
            'props' => VueProps::for([
                'announcement' => [
                    'subject' => $announcement->subject,
                    'paragraphs' => $announcement->paragraphs(),
                    'action_label' => $announcement->action_label,
                    'action_url' => $announcement->hasAction() ? $announcement->action_url : null,
                ],
                'facts' => [
                    ['label' => __('props.admin.penerima'), 'value' => $announcement->audience->label()],
                    ['label' => __('props.admin.dihantar_kepada'), 'value' => $sent ? __('props.units.recipients', ['count' => $announcement->recipients_count]) : '—'],
                    ['label' => __('props.admin.status'), 'value' => $announcement->status->label(), 'tone' => $announcement->status->tone()],
                    ['label' => __('props.admin.tarikh_hantar'), 'value' => $announcement->sent_at?->translatedFormat('j M Y, g:i A') ?? '—'],
                    ['label' => __('props.admin.ditulis_oleh'), 'value' => $announcement->author?->name ?? 'Admin'],
                ],
                // Accounts first, then the addresses that belong to nobody.
                'recipients' => $announcement->audience->isCustom()
                    ? $announcement->users
                        ->map(fn (User $user): array => ['name' => $user->name, 'email' => $user->email])
                        ->concat(collect($announcement->custom_emails ?? [])
                            ->map(fn (string $email): array => ['name' => $email, 'email' => 'tiada akaun']))
                        ->values()
                    : [],
            ]),
        ]);
    }

    /**
     * Send the draft to the admin writing it and nobody else, so it can be read
     * in a real inbox before it reaches thousands. Nothing is recorded.
     */
    public function test(StoreAnnouncementRequest $request): RedirectResponse
    {
        $draft = new Announcement([
            ...$request->safe()->only(['audience', 'subject', 'body', 'action_label', 'action_url']),
            'status' => AnnouncementStatus::Draft,
        ]);

        // Sent now, not queued: the draft is never saved, and a queued job can
        // only carry a model it can load back from the database.
        $request->user()->notifyNow(new AnnouncementPublished($draft, mailOnly: true));

        return back()->with('status', __('flash.admin.test_sent', ['email' => $request->user()->email]));
    }

    private function audienceSummary(Announcement $announcement): string
    {
        if (! $announcement->audience->isCustom()) {
            return $announcement->audience->label();
        }

        $picked = $announcement->users()->count() + count($announcement->custom_emails ?? []);

        return $picked.' penerima pilihan';
    }
}
