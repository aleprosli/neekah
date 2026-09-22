<?php

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use App\Support\AnnouncementPresets;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->aina = User::factory()->create(['name' => 'Aina']);
    $this->hakim = User::factory()->create(['name' => 'Hakim']);
    $this->vendor = User::factory()->vendor()->create(['name' => 'Studio Seri']);
});

it('queues an announcement rather than sending it in the request', function () {
    Queue::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.store'), [
            'audience' => 'everyone',
            'subject' => 'Checklist majlis kini lebih lengkap',
            'body' => "Kami baru tambah fasa dokumen nikah.\n\nBuka checklist anda untuk melihatnya.",
        ])
        ->assertRedirect(route('admin.announcements.index'));

    $announcement = Announcement::sole();

    expect($announcement->subject)->toBe('Checklist majlis kini lebih lengkap')
        ->and($announcement->status)->toBe(AnnouncementStatus::Draft)
        ->and($announcement->user_id)->toBe($this->admin->id);

    Queue::assertPushed(SendAnnouncement::class);
});

it('reaches every couple and vendor, by email and in the bell', function () {
    Notification::fake();

    $announcement = Announcement::factory()->create(['audience' => 'everyone', 'action_label' => null, 'action_url' => null]);
    (new SendAnnouncement($announcement))->handle();

    Notification::assertSentTo([$this->aina, $this->hakim, $this->vendor], AnnouncementPublished::class);
    Notification::assertNotSentTo($this->admin, AnnouncementPublished::class);

    $announcement->refresh();
    expect($announcement->status)->toBe(AnnouncementStatus::Sent)
        ->and($announcement->recipients_count)->toBe(3)
        ->and($announcement->sent_at)->not->toBeNull();
});

it('sends to couples only, or vendors only, when that is the audience', function () {
    Notification::fake();

    (new SendAnnouncement(Announcement::factory()->create(['audience' => 'customers'])))->handle();

    Notification::assertSentTo([$this->aina, $this->hakim], AnnouncementPublished::class);
    Notification::assertNotSentTo($this->vendor, AnnouncementPublished::class);

    Notification::fake();

    (new SendAnnouncement(Announcement::factory()->create(['audience' => 'vendors'])))->handle();

    Notification::assertSentTo($this->vendor, AnnouncementPublished::class);
    Notification::assertNotSentTo([$this->aina, $this->hakim], AnnouncementPublished::class);
});

it('leaves out an account that has been deactivated', function () {
    Notification::fake();
    $this->hakim->update(['deactivated_at' => now()]);

    $announcement = Announcement::factory()->create(['audience' => 'everyone']);
    (new SendAnnouncement($announcement))->handle();

    Notification::assertNotSentTo($this->hakim, AnnouncementPublished::class);
    expect($announcement->fresh()->recipients_count)->toBe(2);
});

it('writes the subject, every paragraph and the button into the email', function () {
    $announcement = Announcement::factory()->create([
        'subject' => 'Yuran platform dikemas kini',
        'body' => "Perenggan pertama.\n\nPerenggan kedua.",
        'action_label' => 'Buka dashboard',
        'action_url' => 'https://neekah.my/vendor',
    ]);

    $mail = (new AnnouncementPublished($announcement))->toMail($this->aina);
    $html = (string) $mail->render();

    expect($mail->subject)->toBe('Yuran platform dikemas kini')
        ->and($html)->toContain('Perenggan pertama.')
        ->toContain('Perenggan kedua.')
        ->toContain('Buka dashboard')
        ->toContain('https://neekah.my/vendor');
});

it('lands in the notification bell with a link back to the announcement action', function () {
    $announcement = Announcement::factory()->create(['action_label' => 'Buka checklist', 'action_url' => 'https://neekah.my/checklist']);

    $data = (new AnnouncementPublished($announcement))->toDatabase($this->aina);

    expect($data['title'])->toBe($announcement->subject)
        ->and($data['url'])->toBe('https://neekah.my/checklist');
});

it('sends a test to the admin alone and records nothing', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.test'), [
            'audience' => 'everyone',
            'subject' => 'Ujian',
            'body' => 'Sekadar ujian.',
        ])
        ->assertRedirect();

    Notification::assertSentTo($this->admin, AnnouncementPublished::class, function (AnnouncementPublished $notification): bool {
        return $notification->mailOnly && $notification->via($this->admin) === ['mail'];
    });
    Notification::assertNotSentTo([$this->aina, $this->vendor], AnnouncementPublished::class);

    expect(Announcement::count())->toBe(0);
});

it('refuses half a button, and refuses anyone who is not an admin', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.announcements.store'), [
            'audience' => 'everyone',
            'subject' => 'Tajuk',
            'body' => 'Isi.',
            'action_label' => 'Buka',
        ])
        ->assertSessionHasErrors('action_url');

    $this->actingAs($this->aina)
        ->post(route('admin.announcements.store'), ['audience' => 'everyone', 'subject' => 'Tajuk', 'body' => 'Isi.'])
        ->assertForbidden();

    $this->actingAs($this->aina)->get(route('admin.announcements.index'))->assertForbidden();
    expect(Announcement::count())->toBe(0);
});

it('counts the audience on the page so an admin knows who they are about to reach', function () {
    Announcement::factory()->sent()->create();

    $this->actingAs($this->admin)
        ->get(route('admin.announcements.index'))
        ->assertOk()
        ->assertViewHas('props', function (array $props): bool {
            $audiences = collect($props['audiences']);

            return $audiences->firstWhere('value', 'everyone')['count'] === 3
                && $audiences->firstWhere('value', 'customers')['count'] === 2
                && $audiences->firstWhere('value', 'vendors')['count'] === 1
                && count($props['announcements']) === 1;
        });
});

it('shows what was sent, to whom and when', function () {
    $announcement = Announcement::factory()->sent()->create([
        'subject' => 'Cuti Hari Raya',
        'body' => "Pejabat tutup.\n\nJumpa lagi selepas raya.",
        'action_label' => 'Baca lanjut',
        'action_url' => 'https://neekah.my/blog',
    ]);

    $this->actingAs($this->admin)
        ->get(route('admin.announcements.show', $announcement))
        ->assertOk()
        ->assertSee('Cuti Hari Raya')
        ->assertSee('Pejabat tutup.')
        ->assertSee('Jumpa lagi selepas raya.')
        ->assertSee('Baca lanjut')
        ->assertSee($announcement->recipients_count.' penerima');

    $this->actingAs($this->aina)->get(route('admin.announcements.show', $announcement))->assertForbidden();
});

it('sends to a list picked by hand, and to nobody else', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.store'), [
            'audience' => 'custom',
            'subject' => 'Jemputan sesi demo',
            'body' => 'Kami nak tunjuk sesuatu.',
            'user_ids' => [$this->aina->id, $this->vendor->id],
        ])
        ->assertRedirect(route('admin.announcements.index'));

    $announcement = Announcement::sole();
    (new SendAnnouncement($announcement))->handle();

    Notification::assertSentTo([$this->aina, $this->vendor], AnnouncementPublished::class);
    Notification::assertNotSentTo([$this->hakim, $this->admin], AnnouncementPublished::class);

    expect($announcement->fresh()->recipients_count)->toBe(2)
        ->and($announcement->users()->pluck('users.id')->sort()->values()->all())
        ->toBe(collect([$this->aina->id, $this->vendor->id])->sort()->values()->all());
});

it('mails an address typed by hand that belongs to no account', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.store'), [
            'audience' => 'custom',
            'subject' => 'Jemputan',
            'body' => 'Isi.',
            'emails' => "orang@luar.test\nsatu.lagi@luar.test",
        ])
        ->assertRedirect();

    $announcement = Announcement::sole();
    expect($announcement->custom_emails)->toBe(['orang@luar.test', 'satu.lagi@luar.test']);

    (new SendAnnouncement($announcement))->handle();

    Notification::assertSentOnDemand(AnnouncementPublished::class, function ($notification, $channels, $notifiable): bool {
        return $notifiable->routes['mail'] === 'orang@luar.test';
    });
    expect($announcement->fresh()->recipients_count)->toBe(2);
});

it('treats a typed address that has an account as that account', function () {
    Notification::fake();

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.store'), [
            'audience' => 'custom',
            'subject' => 'Jemputan',
            'body' => 'Isi.',
            'emails' => strtoupper($this->aina->email),
        ])
        ->assertRedirect();

    $announcement = Announcement::sole();

    expect($announcement->custom_emails)->toBe([])
        ->and($announcement->users()->pluck('users.id')->all())->toBe([$this->aina->id]);

    (new SendAnnouncement($announcement))->handle();

    // The bell as well as the email, and counted once.
    Notification::assertSentTo($this->aina, AnnouncementPublished::class);
    expect($announcement->fresh()->recipients_count)->toBe(1);
});

it('refuses a hand-picked announcement with nobody in it, and a bad address', function () {
    $this->actingAs($this->admin)
        ->post(route('admin.announcements.store'), ['audience' => 'custom', 'subject' => 'Tajuk', 'body' => 'Isi.'])
        ->assertSessionHasErrors('user_ids');

    $this->actingAs($this->admin)
        ->post(route('admin.announcements.store'), [
            'audience' => 'custom',
            'subject' => 'Tajuk',
            'body' => 'Isi.',
            'emails' => 'bukan-emel',
        ])
        ->assertSessionHasErrors('emails');

    expect(Announcement::count())->toBe(0);
});

it('leaves a hand-picked account out once it is deactivated', function () {
    Notification::fake();

    $announcement = Announcement::factory()->create(['audience' => 'custom']);
    $announcement->users()->attach([$this->aina->id, $this->hakim->id]);
    $this->hakim->update(['deactivated_at' => now()]);

    (new SendAnnouncement($announcement))->handle();

    Notification::assertSentTo($this->aina, AnnouncementPublished::class);
    Notification::assertNotSentTo($this->hakim, AnnouncementPublished::class);
    expect($announcement->fresh()->recipients_count)->toBe(1);
});

it('searches accounts for the picker without offering admins or deactivated ones', function () {
    $this->hakim->update(['deactivated_at' => now()]);

    $this->actingAs($this->admin)
        ->getJson(route('admin.announcements.recipients', ['search' => 'Aina']))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Aina')
        ->assertJsonCount(1, 'data');

    // The admin writing it, and an account that has been deactivated, are not offered.
    $this->actingAs($this->admin)
        ->getJson(route('admin.announcements.recipients', ['search' => 'Hakim']))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->actingAs($this->admin)
        ->getJson(route('admin.announcements.recipients', ['search' => $this->admin->email]))
        ->assertOk()
        ->assertJsonCount(0, 'data');

    $this->actingAs($this->aina)->getJson(route('admin.announcements.recipients'))->assertForbidden();
});

it('names everyone a hand-picked announcement went to', function () {
    $announcement = Announcement::factory()->sent()->create([
        'audience' => 'custom',
        'custom_emails' => ['orang@luar.test'],
    ]);
    $announcement->users()->attach($this->aina);

    $this->actingAs($this->admin)
        ->get(route('admin.announcements.show', $announcement))
        ->assertOk()
        ->assertSee('Aina')
        ->assertSee($this->aina->email)
        ->assertSee('orang@luar.test')
        ->assertSee('tiada akaun');
});

/*
|--------------------------------------------------------------------------
| Presets
|--------------------------------------------------------------------------
|
| A preset only fills the form. What matters is that every one of them is a
| message the application would actually accept and send, and that the button
| on it points somewhere real.
|
*/

it('offers a preset for each message the admin keeps rewriting', function () {
    $presets = $this->actingAs($this->admin)
        ->get(route('admin.announcements.index'))
        ->assertOk()
        ->viewData('props')['presets'];

    expect(collect($presets)->pluck('key')->all())
        ->toContain('vendor_profile', 'vendor_catalogue', 'vendor_response', 'feature_launch', 'card_designs', 'couple_start', 'guest_links', 'maintenance');

    foreach ($presets as $preset) {
        expect($preset['label'])->not->toBe('pages.announcement_presets.'.$preset['key'])
            ->and($preset['hint'])->not->toBe('pages.announcement_preset_hints.'.$preset['key'])
            ->and(AnnouncementAudience::tryFrom($preset['audience']))->not->toBeNull();
    }
});

it('sends every preset as it stands, without the admin having to fix it first', function () {
    Notification::fake();

    foreach (AnnouncementPresets::all() as $preset) {
        // The test send goes to the admin alone, and runs the same validation a
        // real send does, so a preset that is too long or missing half a button
        // fails here rather than in front of 775 people.
        $this->actingAs($this->admin)
            ->post(route('admin.announcements.test'), [
                'audience' => $preset['audience'],
                'subject' => $preset['subject'],
                'body' => $preset['body'],
                'action_label' => $preset['action_label'],
                'action_url' => $preset['action_url'],
            ])
            ->assertSessionHasNoErrors();
    }

    Notification::assertSentToTimes($this->admin, AnnouncementPublished::class, count(AnnouncementPresets::all()));
});

it('points every preset button at a page on Neekah, in Malay', function () {
    foreach (AnnouncementPresets::all() as $preset) {
        if ($preset['action_url'] === null) {
            expect($preset['action_label'])->toBeNull();

            continue;
        }

        expect($preset['action_url'])->toStartWith(config('app.url'))
            // The Malay addresses: the message is Malay and those are the URLs
            // that have been shared and indexed.
            ->and($preset['action_url'])->not->toContain('/en/');
    }
});

it('asks the vendors to finish their profile, and nobody else', function () {
    $preset = collect(AnnouncementPresets::all())->firstWhere('key', 'vendor_profile');

    expect($preset['audience'])->toBe(AnnouncementAudience::Vendors->value)
        ->and($preset['subject'])->toContain('profil')
        ->and($preset['action_url'])->toBe(url()->routeIn('ms', 'vendor.profile.edit'));
});
