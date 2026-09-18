---
paths:
  - 'app/Jobs/**'
---

# Jobs

## Admin Telegram alerts go through SendTelegramAlert::about()
New signups tell the admin Telegram chat through SendTelegramAlert::about($headline, $rows), called from RegisterVendor, Auth\RegisterController and Auth\GoogleController. about() checks TelegramSettings::isEnabled() and simply does nothing when Telegram is not configured, so nothing needs to guard the call site. The job is queued and logs a warning instead of throwing when Telegram refuses — a signup must never fail because of an alert. Rows are escaped; only the headline may carry HTML, and it is always our own literal string.

## Announcements go out queued, and never to an admin or a deactivated account
Admin -> Pengumuman writes an Announcement row and dispatches SendAnnouncement, which chunkById(200)s over AnnouncementAudience::recipients() and sends AnnouncementPublished (mail + database, so it lands in the bell too). AnnouncementAudience::Custom is a list picked by hand — accounts attached through announcement_user, plus addresses typed in that belong to no account (announcements.custom_emails), which are mailed on demand and get no notification bell. A typed address that does match an account is stored as that account, so nobody is mailed twice. Announcement::recipientQuery() is what the job asks; the enum's own recipients() matches nobody for Custom on purpose.

The audience query is the single place that decides who is reached: admins are excluded (an announcement is what the platform says to its users) and so is any deactivated_at account, which EnsureAccountIsActive would sign straight back out. Keep both exclusions in the enum, not in the job.

The job marks the row Sending -> Sent with recipients_count and sent_at, and failed() drops it back to Draft so a run that died never reads as delivered. Body is plain text, one MailMessage line per paragraph, with an optional action button that needs both label and URL. "Hantar ujian kepada saya" passes mailOnly: true and records nothing. Covered by Admin/AnnouncementTest.
