<?php

namespace App\Actions;

use App\Enums\ThreadsPostStatus;
use App\Jobs\SendTelegramAlert;
use App\Models\ThreadsPost;
use App\Support\Threads\ThreadsApiException;
use App\Support\Threads\ThreadsClient;
use App\Support\ThreadsSettings;
use Illuminate\Support\Str;

/**
 * Move one post a step further towards being live. neekah:threads-publish
 * calls it once a minute per post, which is the wait Threads asks for between
 * creating a container and publishing it:
 *
 *   Scheduled  → container created              → Processing
 *   Processing → container FINISHED, published   → Published
 *              → container ERROR or EXPIRED      → Failed
 *
 * A temporary failure (Threads down, rate limited) is tried again on the next
 * run, up to MAX_ATTEMPTS; anything else fails the post at once and tells the
 * admin Telegram chat, because a post Threads refused will not go through by
 * itself.
 */
class PublishThreadsPost
{
    public const MAX_ATTEMPTS = 3;

    /** Threads usually needs half a minute for an image; past this it is not coming. */
    public const PROCESSING_TIMEOUT_MINUTES = 10;

    public function __construct(private ThreadsClient $client, private ThreadsSettings $settings) {}

    public function handle(ThreadsPost $post): void
    {
        try {
            match ($post->status) {
                ThreadsPostStatus::Scheduled => $this->createContainer($post),
                ThreadsPostStatus::Processing => $this->publishWhenReady($post),
                default => null,
            };
        } catch (ThreadsApiException $exception) {
            $attempts = $post->attempts + 1;

            if ($exception->temporary && $attempts < self::MAX_ATTEMPTS) {
                $post->update(['attempts' => $attempts, 'error' => $exception->getMessage()]);

                return;
            }

            $this->fail($post->fill(['attempts' => $attempts]), $exception->getMessage());
        }
    }

    private function createContainer(ThreadsPost $post): void
    {
        $imageUrl = $post->imageUrl();
        $poll = $post->pollAttachment();

        $containerId = $this->client->createContainer($this->settings->userId(), $this->settings->accessToken(), [
            'media_type' => $imageUrl ? 'IMAGE' : 'TEXT',
            'text' => $post->text,
            'image_url' => $imageUrl,
            // Text posts only; a poll takes the place of the link preview.
            'link_attachment' => $imageUrl || $poll ? null : $post->link_url,
            'poll_attachment' => $poll,
            'topic_tag' => self::topicTag($post->topic_tag),
        ]);

        $post->update([
            'status' => ThreadsPostStatus::Processing,
            'container_id' => $containerId,
            'container_created_at' => now(),
            'error' => null,
        ]);
    }

    private function publishWhenReady(ThreadsPost $post): void
    {
        $container = $this->client->containerStatus($post->container_id, $this->settings->accessToken());

        match ($container['status']) {
            'FINISHED' => $this->publish($post),
            // Published already: our publish call got through but its answer did not.
            'PUBLISHED' => $post->update(['status' => ThreadsPostStatus::Published, 'published_at' => now(), 'error' => null]),
            'ERROR', 'EXPIRED' => $this->fail($post, $container['error_message'] ?: $container['status']),
            default => $post->container_created_at?->lt(now()->subMinutes(self::PROCESSING_TIMEOUT_MINUTES))
                ? $this->fail($post, 'Threads was still processing the post after '.self::PROCESSING_TIMEOUT_MINUTES.' minutes.')
                : null,
        };
    }

    private function publish(ThreadsPost $post): void
    {
        $mediaId = $this->client->publish($this->settings->userId(), $this->settings->accessToken(), $post->container_id);

        $post->update([
            'status' => ThreadsPostStatus::Published,
            'threads_media_id' => $mediaId,
            'published_at' => now(),
            'error' => null,
        ]);

        // Only for the "Lihat di Threads" link; the post is live without it.
        try {
            $post->update(['permalink' => $this->client->permalink($mediaId, $this->settings->accessToken())]);
        } catch (ThreadsApiException) {
        }
    }

    private function fail(ThreadsPost $post, string $reason): void
    {
        $post->fill(['status' => ThreadsPostStatus::Failed, 'error' => $reason])->save();

        SendTelegramAlert::about('🧵 <b>Threads post failed</b>', [
            'Type' => $post->type->label(),
            'Scheduled' => $post->scheduled_at->format('j M Y, g:i A'),
            'Text' => Str::limit($post->text, 120),
            'Reason' => $reason,
        ]);
    }

    /**
     * Threads takes a topic of 1–50 characters with no full stop or
     * ampersand, and refuses the whole post over one that breaks the rule.
     */
    public static function topicTag(?string $tag): ?string
    {
        $tag = trim(str_replace(['.', '&', '#'], '', (string) $tag));

        return $tag === '' ? null : mb_substr($tag, 0, 50);
    }
}
