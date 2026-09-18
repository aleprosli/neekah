---
paths:
  - 'app/Notifications/**'
---

# Notifications

## Every notification is queued, and jobs wait for the commit
Every class in app/Notifications implements ShouldQueue (NotificationQueueTest fails a new one that forgets). Sent inline, each notification held the request open for an SMTP round trip; sign-up got slow enough that people tapped twice, and on production two Google sign-ups got two welcome emails and two Telegram alerts (18 Sep 2026). The one exception is the announcement test send, which uses notifyNow() because its draft is never saved and a queued job can only carry a model it can reload.

config/queue.php sets after_commit => true on the database connection: bookings, payments and vendor sign-up notify from inside DB::transaction, and without it the worker could pick a job up before the booking it is about exists.

Anything that must happen once per sign-up (welcome email, Telegram alert) needs a guard that survives two concurrent requests — a session flag does not, since both requests read the session before either writes it back. PhoneNumberController uses an atomic `whereNull('phone')->update()` so only one request finishes the signup. On the client, resources/js/form-guard.js stops a second submit of any POST form and marks the button busy (never `disabled`, which would drop the pressed button from the form data).
