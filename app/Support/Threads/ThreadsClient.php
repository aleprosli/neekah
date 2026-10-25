<?php

namespace App\Support\Threads;

use App\Support\ThreadsSettings;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * The calls Neekah makes to the Threads API: connect the account (OAuth code →
 * short-lived token → 60-day token), keep that token alive, and publish.
 *
 * Publishing is two steps on Threads' side: a container is created with the
 * text and the image address, Threads downloads the image, and only once the
 * container reads FINISHED is it published. PublishThreadsPost walks a post
 * through them a minute apart.
 */
class ThreadsClient
{
    public function authorizeUrl(string $state): string
    {
        return config('services.threads.authorize_url').'?'.http_build_query([
            'client_id' => config('services.threads.app_id'),
            'redirect_uri' => ThreadsSettings::redirectUri(),
            'scope' => implode(',', ThreadsSettings::SCOPES),
            'response_type' => 'code',
            'state' => $state,
        ]);
    }

    /**
     * The code Meta sent back, swapped for a token that lasts an hour.
     *
     * @return array{access_token: string, user_id: string}
     */
    public function exchangeCode(string $code): array
    {
        $response = $this->send(fn () => $this->http()->asForm()->post('/oauth/access_token', [
            'client_id' => config('services.threads.app_id'),
            'client_secret' => config('services.threads.app_secret'),
            'grant_type' => 'authorization_code',
            'redirect_uri' => ThreadsSettings::redirectUri(),
            'code' => $code,
        ]));

        return [
            'access_token' => (string) $response->json('access_token'),
            'user_id' => (string) $response->json('user_id'),
        ];
    }

    /**
     * An hour-long token swapped for one that lasts 60 days.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function exchangeForLongLived(string $shortLivedToken): array
    {
        return $this->tokenFrom($this->send(fn () => $this->http()->get('/access_token', [
            'grant_type' => 'th_exchange_token',
            'client_secret' => config('services.threads.app_secret'),
            'access_token' => $shortLivedToken,
        ])));
    }

    /**
     * A fresh 60 days for a long-lived token that is at least a day old and
     * not yet expired. An expired one cannot be refreshed: the admin connects
     * again.
     *
     * @return array{access_token: string, expires_in: int}
     */
    public function refresh(string $accessToken): array
    {
        return $this->tokenFrom($this->send(fn () => $this->http()->get('/refresh_access_token', [
            'grant_type' => 'th_refresh_token',
            'access_token' => $accessToken,
        ])));
    }

    /**
     * @return array{id: string, username: string}
     */
    public function profile(string $accessToken): array
    {
        $response = $this->send(fn () => $this->http()->get($this->path('me'), [
            'fields' => 'id,username',
            'access_token' => $accessToken,
        ]));

        return ['id' => (string) $response->json('id'), 'username' => (string) $response->json('username')];
    }

    /**
     * @param  array<string, mixed>  $fields  media_type, text, image_url, link_attachment, topic_tag, poll_attachment
     * @return string The container id.
     */
    public function createContainer(string $userId, string $accessToken, array $fields): string
    {
        $fields = array_filter($fields, fn (mixed $value): bool => $value !== null && $value !== '');

        if (isset($fields['poll_attachment'])) {
            $fields['poll_attachment'] = json_encode($fields['poll_attachment'], JSON_UNESCAPED_UNICODE);
        }

        $response = $this->send(fn () => $this->http()->asForm()->post($this->path($userId.'/threads'), [
            ...$fields,
            'access_token' => $accessToken,
        ]));

        return $this->idFrom($response);
    }

    /**
     * IN_PROGRESS, FINISHED, PUBLISHED, ERROR or EXPIRED, and why when it is ERROR.
     *
     * @return array{status: string, error_message: string|null}
     */
    public function containerStatus(string $containerId, string $accessToken): array
    {
        $response = $this->send(fn () => $this->http()->get($this->path($containerId), [
            'fields' => 'status,error_message',
            'access_token' => $accessToken,
        ]));

        return [
            'status' => (string) $response->json('status'),
            'error_message' => $response->json('error_message'),
        ];
    }

    /**
     * @return string The published post's media id.
     */
    public function publish(string $userId, string $accessToken, string $containerId): string
    {
        return $this->idFrom($this->send(fn () => $this->http()->asForm()->post($this->path($userId.'/threads_publish'), [
            'creation_id' => $containerId,
            'access_token' => $accessToken,
        ])));
    }

    public function permalink(string $mediaId, string $accessToken): ?string
    {
        $response = $this->send(fn () => $this->http()->get($this->path($mediaId), [
            'fields' => 'permalink',
            'access_token' => $accessToken,
        ]));

        return $response->json('permalink') ?: null;
    }

    private function http(): PendingRequest
    {
        return Http::baseUrl((string) config('services.threads.graph_url'))->acceptJson()->timeout(20);
    }

    private function path(string $path): string
    {
        return '/'.config('services.threads.version').'/'.$path;
    }

    /**
     * Make the call and turn every way it can go wrong into one exception
     * carrying Meta's own words.
     *
     * @param  callable(): Response  $call
     */
    private function send(callable $call): Response
    {
        try {
            $response = $call();
        } catch (ConnectionException $exception) {
            throw new ThreadsApiException('Threads could not be reached: '.$exception->getMessage(), temporary: true);
        }

        if ($response->successful()) {
            return $response;
        }

        $message = $response->json('error.error_user_msg')
            ?: $response->json('error.message')
            ?: $response->json('error_message')
            ?: 'HTTP '.$response->status();

        throw new ThreadsApiException((string) $message, temporary: $response->serverError() || $response->status() === 429);
    }

    private function idFrom(Response $response): string
    {
        $id = (string) $response->json('id');

        if ($id === '') {
            throw new ThreadsApiException('Threads answered without an id.');
        }

        return $id;
    }

    /**
     * @return array{access_token: string, expires_in: int}
     */
    private function tokenFrom(Response $response): array
    {
        $token = (string) $response->json('access_token');

        if ($token === '') {
            throw new ThreadsApiException('Threads answered without an access token.');
        }

        return ['access_token' => $token, 'expires_in' => (int) ($response->json('expires_in') ?: 60 * 24 * 60 * 60)];
    }
}
