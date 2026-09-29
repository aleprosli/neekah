---
paths:
  - 'resources/views/vendor/mail/**'
---

# Mail

## Email florals are PNGs, and there is no "having trouble clicking" subcopy
Every notification email wears the marketplace hero's peonies (header corners) and the gold divider-floral (header + footer). They are PNGs in public/img/mail, rendered from public/img/layers/*.svg with headless Chrome: Gmail drops SVG and no client understands the CSS masks <x-site.ornament> uses. The corners sit in their own table cells rather than a background image because Outlook ignores backgrounds. Page paper is ivory #fdf8f2, not grey.
resources/views/vendor/notifications/email.blade.php is published only to drop Laravel's English "If you're having trouble clicking the button" subcopy — the owner asked for it gone (29 Sep 2026). Do not re-add it.
Announcement bodies: AnnouncementPublished::mailParagraph() turns "- " lines into a real Markdown list (escaped with e()), because MailMessage::line() folds all lines of a string into one.
