<?php

namespace App\Support;

use App\Enums\ThreadsPostType;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Throwable;

/**
 * Neekah's own Threads account and its daily posting plan, under Admin → Threads.
 *
 * The app id and secret live in .env (services.threads), never in the database.
 * The access token is the account's, given by Meta when an admin connects, and
 * lasts 60 days: it is stored encrypted here and refreshed by
 * neekah:threads-token long before it runs out.
 */
class ThreadsSettings extends SettingGroup
{
    /** The scopes asked for on connect: read the profile, publish, and read insights later. */
    public const SCOPES = ['threads_basic', 'threads_content_publish', 'threads_manage_insights'];

    /**
     * Seven posts a day, spread over the hours people scroll, and mostly
     * vendors: they are what the account is for.
     *
     * @var list<array{time: string, type: string}>
     */
    public const DEFAULT_SLOTS = [
        ['time' => '08:30', 'type' => 'tip'],
        ['time' => '10:30', 'type' => 'listing'],
        ['time' => '12:45', 'type' => 'engagement'],
        ['time' => '15:30', 'type' => 'listing'],
        ['time' => '18:00', 'type' => 'blog'],
        ['time' => '20:30', 'type' => 'engagement'],
        ['time' => '22:00', 'type' => 'listing'],
    ];

    public const MAX_SLOTS = 12;

    public const MAX_DAYS_AHEAD = 7;

    /** Whether the Meta app's id and secret are in .env, which connecting needs. */
    public static function isConfigured(): bool
    {
        return filled(config('services.threads.app_id')) && filled(config('services.threads.app_secret'));
    }

    /**
     * Where Meta sends the admin back after they allow the app. Always the
     * Malay address, because it must match the one registered in the Meta app
     * character for character, whatever language the admin is browsing in.
     */
    public static function redirectUri(): string
    {
        return (string) url()->routeIn(Locales::DEFAULT, 'admin.threads.callback');
    }

    public function isConnected(): bool
    {
        return $this->userId() !== '' && $this->accessToken() !== '' && ! $this->tokenHasExpired();
    }

    /** Auto-posting is on and has an account to post to. */
    public function isEnabled(): bool
    {
        return (bool) $this->value('enabled') && $this->isConnected();
    }

    public function isSwitchedOn(): bool
    {
        return (bool) $this->value('enabled');
    }

    public function userId(): string
    {
        return $this->string('user_id');
    }

    public function username(): string
    {
        return $this->string('username');
    }

    public function accessToken(): string
    {
        $stored = $this->string('access_token');

        if ($stored === '') {
            return '';
        }

        try {
            return Crypt::decryptString($stored);
        } catch (Throwable) {
            // Encrypted under an APP_KEY that is gone: as good as not connected.
            return '';
        }
    }

    public function tokenExpiresAt(): ?Carbon
    {
        $stored = $this->string('token_expires_at');

        return $stored === '' ? null : Carbon::parse($stored);
    }

    public function tokenRefreshedAt(): ?Carbon
    {
        $stored = $this->string('token_refreshed_at');

        return $stored === '' ? null : Carbon::parse($stored);
    }

    public function tokenHasExpired(): bool
    {
        return $this->tokenExpiresAt()?->isPast() ?? false;
    }

    /** The topic every post carries unless its template names another. */
    public function topicTag(): string
    {
        return $this->string('topic_tag');
    }

    /** How many days ahead the planner fills, today counted. */
    public function daysAhead(): int
    {
        return max(1, min(self::MAX_DAYS_AHEAD, (int) $this->value('days_ahead')));
    }

    /** How long a vendor waits before the planner features them again. */
    public function listingCooldownDays(): int
    {
        return max(1, (int) $this->value('listing_cooldown_days'));
    }

    /**
     * The daily plan, earliest first.
     *
     * @return list<array{time: string, type: ThreadsPostType}>
     */
    public function slots(): array
    {
        $stored = json_decode($this->string('slots'), true);

        return collect(is_array($stored) ? $stored : self::DEFAULT_SLOTS)
            ->filter(fn (mixed $slot): bool => is_array($slot)
                && preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', (string) ($slot['time'] ?? '')) === 1
                && ThreadsPostType::tryFrom((string) ($slot['type'] ?? '')) !== null)
            ->map(fn (array $slot): array => ['time' => $slot['time'], 'type' => ThreadsPostType::from($slot['type'])])
            ->sortBy('time')
            ->values()
            ->all();
    }

    /**
     * @param  list<array{time: string, type: string}>  $slots
     */
    public function saveSlots(array $slots): void
    {
        $this->save(['slots' => json_encode(array_values($slots))]);
    }

    /** Keep the account Meta handed back on connect or refresh. */
    public function saveConnection(string $userId, string $username, string $accessToken, int $expiresIn): void
    {
        $this->save([
            'user_id' => $userId,
            'username' => $username,
            'access_token' => Crypt::encryptString($accessToken),
            'token_expires_at' => now()->addSeconds($expiresIn)->toIso8601String(),
            'token_refreshed_at' => now()->toIso8601String(),
        ]);
    }

    public function saveRefreshedToken(string $accessToken, int $expiresIn): void
    {
        $this->saveConnection($this->userId(), $this->username(), $accessToken, $expiresIn);
    }

    public function forgetConnection(): void
    {
        $this->save([
            'enabled' => false,
            'user_id' => '',
            'username' => '',
            'access_token' => '',
            'token_expires_at' => '',
            'token_refreshed_at' => '',
        ]);
    }

    /**
     * @return array<string, string|int|bool>
     */
    public static function defaults(): array
    {
        return [
            'enabled' => false,
            'slots' => (string) json_encode(self::DEFAULT_SLOTS),
            'topic_tag' => 'Perkahwinan',
            'days_ahead' => 2,
            'listing_cooldown_days' => 30,
            'user_id' => '',
            'username' => '',
            'access_token' => '',
            'token_expires_at' => '',
            'token_refreshed_at' => '',
        ];
    }

    protected static function prefix(): string
    {
        return 'threads';
    }
}
