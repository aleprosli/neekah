<?php

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Every notification goes through the queue. Sent inline, each one held the
 * request open for an SMTP round trip, which is what made sign-up slow enough
 * for people to tap the button twice. A new notification that forgets
 * ShouldQueue fails here instead of quietly slowing a page down.
 */
it('queues every notification', function () {
    $notifications = collect(File::files(app_path('Notifications')))
        ->map(fn ($file): string => 'App\\Notifications\\'.Str::beforeLast($file->getFilename(), '.php'))
        ->filter(fn (string $class): bool => is_subclass_of($class, Notification::class));

    expect($notifications)->not->toBeEmpty();

    $notifications->each(fn (string $class) => expect(is_subclass_of($class, ShouldQueue::class))
        ->toBeTrue("{$class} must implement ShouldQueue"));
});

it('holds queued jobs until the transaction around them commits', function () {
    // Bookings, payments and vendor sign-up notify from inside DB::transaction.
    expect(config('queue.connections.database.after_commit'))->toBeTrue();
});
