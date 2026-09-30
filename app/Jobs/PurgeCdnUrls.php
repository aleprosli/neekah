<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

/**
 * Ask Cloudflare to forget files that were just deleted, so a removed photo
 * stops being served from the edge at once instead of when its cache runs
 * out. Does nothing until a zone and an API token (Cache Purge permission)
 * are configured.
 */
class PurgeCdnUrls implements ShouldQueue
{
    use Queueable;

    /** Most URLs one purge request accepts on every Cloudflare plan. */
    public const PER_REQUEST = 30;

    public int $tries = 3;

    /**
     * @param  array<int, string>  $urls
     */
    public function __construct(public array $urls) {}

    public static function configured(): bool
    {
        return filled(config('services.cloudflare.zone_id')) && filled(config('services.cloudflare.api_token'));
    }

    /**
     * Queue a purge for these URLs, when Cloudflare is configured.
     *
     * @param  array<int, string|null>  $urls
     */
    public static function for(array $urls): void
    {
        $urls = array_values(array_unique(array_filter($urls, fn (?string $url): bool => filled($url) && str_starts_with($url, 'http'))));

        if ($urls === [] || ! self::configured()) {
            return;
        }

        foreach (array_chunk($urls, self::PER_REQUEST) as $chunk) {
            dispatch(new self($chunk));
        }
    }

    public function handle(): void
    {
        Http::withToken((string) config('services.cloudflare.api_token'))
            ->timeout(15)
            ->post('https://api.cloudflare.com/client/v4/zones/'.config('services.cloudflare.zone_id').'/purge_cache', ['files' => $this->urls])
            ->throw();
    }
}
