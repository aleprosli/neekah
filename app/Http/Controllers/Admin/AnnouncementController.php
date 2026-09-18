<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAnnouncementRequest;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use App\Notifications\AnnouncementPublished;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class AnnouncementController extends Controller
{
    /**
     * Write something and tell every couple, every vendor, or both. The page
     * carries the live recipient count per audience, because "send to everyone"
     * should say how many people that is before it is pressed.
     */
    public function index(): View
    {
        $announcements = Announcement::with('author')->latest()->limit(50)->get();

        return view('admin.announcements.index', [
            'props' => VueProps::for([
                'storeUrl' => route('admin.announcements.store'),
                'testUrl' => route('admin.announcements.test'),
                'audiences' => collect(AnnouncementAudience::cases())
                    ->map(fn (AnnouncementAudience $audience): array => [
                        'value' => $audience->value,
                        'label' => $audience->label(),
                        'description' => $audience->description(),
                        'count' => $audience->recipients()->count(),
                    ])->values(),
                'announcements' => $announcements->map(fn (Announcement $announcement): array => [
                    'id' => $announcement->id,
                    'subject' => $announcement->subject,
                    'audience' => $announcement->audience->label(),
                    'recipients' => $announcement->status === AnnouncementStatus::Sent
                        ? $announcement->recipients_count.' penerima'
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

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        $announcement = Announcement::create([
            ...$request->safe()->only(['audience', 'subject', 'body', 'action_label', 'action_url']),
            'user_id' => $request->user()->id,
            'status' => AnnouncementStatus::Draft,
        ]);

        SendAnnouncement::dispatch($announcement);

        return redirect()
            ->route('admin.announcements.index')
            ->with('status', 'Pengumuman dihantar kepada '.$announcement->audience->label().'. Emel dihantar melalui queue.');
    }

    public function show(Announcement $announcement): View
    {
        return view('admin.announcements.show', [
            'announcement' => $announcement->load('author'),
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

        $request->user()->notify(new AnnouncementPublished($draft, mailOnly: true));

        return back()->with('status', 'Ujian dihantar ke '.$request->user()->email.'.');
    }
}
