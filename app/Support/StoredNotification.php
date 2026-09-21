<?php

namespace App\Support;

/**
 * A notification as it is shown in the bell and the notifications page.
 *
 * The row stores what happened — a key and its parameters — rather than a
 * finished sentence, because a sentence is written once in one language and
 * stays that way. Translating on the way out means somebody who switches
 * language sees their whole history in it, not just what arrived afterwards.
 *
 * Rows written before this change hold plain strings, and are shown as they
 * are: they were true when they were written, and there is nothing to look up.
 */
class StoredNotification
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{icon: string, title: string, body: string, url: string|null}
     */
    public static function render(array $data): array
    {
        return [
            'icon' => $data['icon'] ?? '🔔',
            'title' => self::text($data, 'title', __('ui.notifications.title')),
            'body' => self::text($data, 'body', ''),
            'url' => $data['url'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private static function text(array $data, string $field, string $fallback): string
    {
        $key = $data[$field.'_key'] ?? null;

        if (is_string($key)) {
            return __($key, (array) ($data[$field.'_params'] ?? []));
        }

        return is_string($data[$field] ?? null) ? $data[$field] : $fallback;
    }
}
