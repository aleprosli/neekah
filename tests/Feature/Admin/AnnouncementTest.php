<?php

use App\Enums\AnnouncementStatus;
use App\Jobs\SendAnnouncement;
use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
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
