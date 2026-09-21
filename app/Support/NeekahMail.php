<?php

namespace App\Support;

use Illuminate\Notifications\Messages\MailMessage;

/**
 * The frame every Neekah email is written in.
 *
 * The greeting and the sign-off were repeated in all sixteen notifications,
 * which meant translating the same two sentences sixteen times and getting one
 * of them wrong.
 */
class NeekahMail
{
    /**
     * Some of these go to an email address rather than to a user — an invited
     * partner has no account yet — and an address has no name to greet.
     */
    public static function to(mixed $notifiable = null): MailMessage
    {
        $name = match (true) {
            is_string($notifiable) => $notifiable,
            is_object($notifiable) => (is_string($notifiable->name ?? null) ? $notifiable->name : null),
            default => null,
        };

        return (new MailMessage)
            ->greeting($name ? __('notifications.greeting', ['name' => $name]) : __('notifications.greeting_plain'))
            ->salutation(__('notifications.salutation'));
    }
}
