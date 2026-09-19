<?php

namespace App\Support;

use Illuminate\Support\Str;

class SocialLinks
{
    /**
     * The platforms a vendor may link to. `base` turns a bare username into a
     * link; `hosts` is what a pasted link must point at, so the Instagram
     * field can never carry a link to somewhere else. A website has neither:
     * it is any https address.
     *
     * @var array<string, array{label: string, base: string|null, hosts: list<string>, placeholder: string}>
     */
    public const PLATFORMS = [
        'instagram' => ['label' => 'Instagram', 'base' => 'https://www.instagram.com/', 'hosts' => ['instagram.com'], 'placeholder' => '@namakedai'],
        'facebook' => ['label' => 'Facebook', 'base' => 'https://www.facebook.com/', 'hosts' => ['facebook.com', 'fb.com', 'fb.me'], 'placeholder' => 'namakedai atau pautan penuh'],
        'tiktok' => ['label' => 'TikTok', 'base' => 'https://www.tiktok.com/@', 'hosts' => ['tiktok.com'], 'placeholder' => '@namakedai'],
        'threads' => ['label' => 'Threads', 'base' => 'https://www.threads.com/@', 'hosts' => ['threads.com', 'threads.net'], 'placeholder' => '@namakedai'],
        'x' => ['label' => 'X (Twitter)', 'base' => 'https://x.com/', 'hosts' => ['x.com', 'twitter.com'], 'placeholder' => '@namakedai'],
        'youtube' => ['label' => 'YouTube', 'base' => 'https://www.youtube.com/@', 'hosts' => ['youtube.com', 'youtu.be'], 'placeholder' => '@namakedai'],
        'website' => ['label' => 'Laman web', 'base' => null, 'hosts' => [], 'placeholder' => 'https://namakedai.com'],
    ];

    /**
     * A vendor's number is only shown to signed-in visitors, so a website
     * field must not become the way around that.
     */
    private const BLOCKED_HOSTS = ['wa.me', 'wa.link', 'whatsapp.com', 't.me', 'telegram.me'];

    /**
     * What the vendor typed, as the link it will be stored and shown as.
     * "@kedai", "kedai" and a pasted address all end up as one https link.
     * Returns null for an empty value and leaves anything unrecognisable as it
     * is, so validation can say what is wrong with it.
     */
    public static function normalise(string $platform, ?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $value)) {
            return preg_replace('#^http://#i', 'https://', $value);
        }

        $base = self::PLATFORMS[$platform]['base'] ?? null;

        // Usernames may hold a dot ("kedai.co"), so a slash is what tells a
        // pasted "instagram.com/kedai" apart from one.
        if ($base === null || str_contains($value, '/')) {
            return 'https://'.$value;
        }

        return $base.ltrim($value, '@');
    }

    /**
     * Whether a normalised link points where its platform says it does.
     */
    public static function isAllowed(string $platform, string $url): bool
    {
        $host = Str::lower((string) parse_url($url, PHP_URL_HOST));

        if (! str_starts_with($url, 'https://') || $host === '' || ! str_contains($host, '.')) {
            return false;
        }

        $matches = fn (array $hosts): bool => collect($hosts)
            ->contains(fn (string $allowed): bool => $host === $allowed || str_ends_with($host, '.'.$allowed));

        $hosts = self::PLATFORMS[$platform]['hosts'] ?? [];

        return $hosts === [] ? ! $matches(self::BLOCKED_HOSTS) : $matches($hosts);
    }
}
