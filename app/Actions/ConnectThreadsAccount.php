<?php

namespace App\Actions;

use App\Support\Threads\ThreadsClient;
use App\Support\ThreadsSettings;

/**
 * Turn the code Meta sends back after an admin allows the app into a 60-day
 * token for that Threads account, and keep it with the account's username.
 */
class ConnectThreadsAccount
{
    public function __construct(private ThreadsClient $client, private ThreadsSettings $settings) {}

    /**
     * @return string The connected account's username.
     */
    public function handle(string $code): string
    {
        $shortLived = $this->client->exchangeCode($code);
        $longLived = $this->client->exchangeForLongLived($shortLived['access_token']);
        $profile = $this->client->profile($longLived['access_token']);

        $this->settings->saveConnection(
            $profile['id'] ?: $shortLived['user_id'],
            $profile['username'],
            $longLived['access_token'],
            $longLived['expires_in'],
        );

        return $profile['username'];
    }
}
