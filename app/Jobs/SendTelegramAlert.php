<?php

namespace App\Jobs;

use App\Support\TelegramSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Tell the admin Telegram chat that something happened. Queued, because a
 * signup must never wait on, or fail because of, Telegram being slow.
 */
class SendTelegramAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  array<string, string|null>  $rows  Label => value, printed one per line.
     */
    public function __construct(
        private readonly string $headline,
        private readonly array $rows = [],
    ) {}

    /**
     * Send the alert for an event, unless Telegram has not been set up.
     *
     * @param  array<string, string|null>  $rows
     */
    public static function about(string $headline, array $rows = []): void
    {
        if (app(TelegramSettings::class)->isEnabled()) {
            self::dispatch($headline, $rows);
        }
    }

    public function handle(TelegramSettings $settings): void
    {
        if (! $settings->isEnabled()) {
            return;
        }

        $response = Http::timeout(10)->post($settings->sendMessageUrl(), [
            'chat_id' => $settings->chatId(),
            'text' => $this->text(),
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ]);

        if ($response->failed()) {
            Log::warning('Telegram alert was refused.', ['status' => $response->status(), 'body' => $response->body()]);
        }
    }

    /**
     * The message body: the environment banner, the headline, then one line per
     * detail. Values are escaped because the message is sent as HTML.
     */
    private function text(): string
    {
        $lines = ['<b>'.e(mb_strtoupper(config('app.name').' '.app()->environment())).'</b>', '', $this->headline];

        foreach (array_filter($this->rows) as $label => $value) {
            $lines[] = e($label).': '.e((string) $value);
        }

        return implode(PHP_EOL, $lines);
    }
}
