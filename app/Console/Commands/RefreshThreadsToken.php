<?php

namespace App\Console\Commands;

use App\Jobs\SendTelegramAlert;
use App\Support\Threads\ThreadsApiException;
use App\Support\Threads\ThreadsClient;
use App\Support\ThreadsSettings;
use Illuminate\Console\Command;

class RefreshThreadsToken extends Command
{
    protected $signature = 'neekah:threads-token';

    protected $description = 'Refresh the Threads access token before its 60 days run out';

    /**
     * Refreshed once it has fewer than this many days left, so a run of
     * failed nights still leaves weeks to notice and reconnect.
     */
    public const REFRESH_WITHIN_DAYS = 30;

    public function handle(ThreadsClient $client, ThreadsSettings $settings): int
    {
        if ($settings->userId() === '' || $settings->accessToken() === '') {
            return self::SUCCESS;
        }

        if ($settings->tokenHasExpired()) {
            // An expired token cannot be refreshed; someone has to connect again.
            SendTelegramAlert::about('🧵 <b>Threads is disconnected</b>', [
                'Account' => '@'.$settings->username(),
                'Action' => 'Admin → Threads → Sambung semula',
            ]);
            $this->components->error('The Threads token has expired; reconnect under Admin → Threads.');

            return self::FAILURE;
        }

        $expiresAt = $settings->tokenExpiresAt();

        if ($expiresAt && $expiresAt->gt(now()->addDays(self::REFRESH_WITHIN_DAYS))) {
            return self::SUCCESS;
        }

        // Threads refuses to refresh a token less than a day old.
        if ($settings->tokenRefreshedAt()?->gt(now()->subDay())) {
            return self::SUCCESS;
        }

        try {
            $token = $client->refresh($settings->accessToken());
        } catch (ThreadsApiException $exception) {
            SendTelegramAlert::about('🧵 <b>Threads token could not be refreshed</b>', [
                'Account' => '@'.$settings->username(),
                'Expires' => $expiresAt?->format('j M Y'),
                'Reason' => $exception->getMessage(),
            ]);
            $this->components->error('Threads refused the refresh: '.$exception->getMessage());

            return self::FAILURE;
        }

        $settings->saveRefreshedToken($token['access_token'], $token['expires_in']);
        $this->components->info('Threads token refreshed until '.$settings->tokenExpiresAt()?->format('j M Y').'.');

        return self::SUCCESS;
    }
}
