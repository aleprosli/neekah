<?php

namespace App\Support\Threads;

use App\Actions\StoreOptimizedImage;
use App\Enums\ThreadsPostStatus;
use App\Enums\ThreadsPostType;
use App\Models\Post;
use App\Models\ThreadsPost;
use App\Models\ThreadsTemplate;
use App\Models\Vendor;
use App\Support\Ringgit;
use App\Support\ThreadsSettings;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes one Threads post of a given type from what Neekah already has: the
 * next vendor in turn, the newest blog post not yet shared, or the next
 * template in the bank. Returns the post's attributes, or null when there is
 * nothing of that type to write from.
 *
 * Posts are written in Malay, the account's language, whatever language the
 * admin who pressed the button was browsing in.
 */
class ThreadsComposer
{
    /** The caption a listing gets when the bank holds no listing template. */
    public const DEFAULT_LISTING_CAPTION = "✨ {nama}\n{kategori} · {negeri}\n\n{tagline}\n\nLihat pakej & portfolio penuh: {pautan}";

    public const DEFAULT_BLOG_CAPTION = "{tajuk}\n\n{ringkasan}\n\nBaca: {pautan}";

    /** A blog post is shared again only after this long. */
    public const BLOG_COOLDOWN_DAYS = 90;

    /** Added to every link, so Analytics can tell what Threads brought in. */
    public const UTM = ['utm_source' => 'threads', 'utm_medium' => 'social', 'utm_campaign' => 'autopost'];

    public function __construct(private ThreadsSettings $settings, private StoreOptimizedImage $images) {}

    /**
     * @return array<string, mixed>|null
     */
    public function compose(ThreadsPostType $type): ?array
    {
        return match ($type) {
            ThreadsPostType::Listing => $this->listing(),
            ThreadsPostType::Blog => $this->blog(),
            default => $this->fromTemplate($type),
        };
    }

    /**
     * The approved vendor with a cover image who has waited longest since
     * their last post (never featured first), skipping anyone featured within
     * the cooldown or already waiting in the queue.
     *
     * @return array<string, mixed>|null
     */
    private function listing(): ?array
    {
        $lastFeatured = fn (QueryBuilder $query) => $query->selectRaw('max(scheduled_at)')
            ->from('threads_posts')
            ->whereColumn('threads_posts.vendor_id', 'vendors.id')
            ->where('threads_posts.status', '!=', ThreadsPostStatus::Cancelled->value);

        $vendor = Vendor::query()
            ->approved()
            ->whereNotNull('cover_image')->where('cover_image', '!=', '')
            ->whereNotExists(fn (QueryBuilder $query) => $query->selectRaw('1')
                ->from('threads_posts')
                ->whereColumn('threads_posts.vendor_id', 'vendors.id')
                ->where('threads_posts.status', '!=', ThreadsPostStatus::Cancelled->value)
                ->where('threads_posts.scheduled_at', '>', now()->subDays($this->settings->listingCooldownDays())))
            ->with('category')
            ->orderBy($lastFeatured)
            ->shuffled((int) now()->format('Ymd'))
            ->first();

        if (! $vendor) {
            return null;
        }

        $template = ThreadsTemplate::query()->nextInTurn(ThreadsPostType::Listing)->first();
        $link = $this->tracked(route('vendors.show', $vendor));

        $values = [
            '{nama}' => $vendor->name,
            '{kategori}' => (string) $vendor->category?->name,
            '{negeri}' => (string) $vendor->state,
            '{tagline}' => (string) $vendor->tagline,
            '{harga}' => (float) $vendor->price_from > 0 ? Ringgit::format($vendor->price_from) : '',
            '{pautan}' => $link,
        ];

        $template?->markUsed();

        return [
            'type' => ThreadsPostType::Listing,
            'text' => $this->fit($template?->body ?? self::DEFAULT_LISTING_CAPTION, $values, '{tagline}'),
            'image_path' => $this->jpegCopy($vendor->cover_image),
            'link_url' => $link,
            'topic_tag' => $template?->topic_tag ?: $this->settings->topicTag(),
            'vendor_id' => $vendor->id,
            'threads_template_id' => $template?->id,
        ];
    }

    /**
     * The newest published blog post not shared in the last three months.
     * Shared as a text post, so Threads shows the article's own preview card.
     *
     * @return array<string, mixed>|null
     */
    private function blog(): ?array
    {
        $post = Post::query()
            ->published()
            ->whereNotExists(fn (QueryBuilder $query) => $query->selectRaw('1')
                ->from('threads_posts')
                ->whereColumn('threads_posts.post_id', 'posts.id')
                ->where('threads_posts.status', '!=', ThreadsPostStatus::Cancelled->value)
                ->where('threads_posts.scheduled_at', '>', now()->subDays(self::BLOG_COOLDOWN_DAYS)))
            ->latest('published_at')
            ->first();

        if (! $post) {
            return null;
        }

        $template = ThreadsTemplate::query()->nextInTurn(ThreadsPostType::Blog)->first();
        $link = $this->tracked($post->url());

        $values = [
            '{tajuk}' => $post->title,
            '{ringkasan}' => Str::limit($post->summary(), 200),
            '{pautan}' => $link,
        ];

        $template?->markUsed();

        return [
            'type' => ThreadsPostType::Blog,
            'text' => $this->fit($template?->body ?? self::DEFAULT_BLOG_CAPTION, $values, '{ringkasan}'),
            'link_url' => $link,
            'topic_tag' => $template?->topic_tag ?: $this->settings->topicTag(),
            'post_id' => $post->id,
            'threads_template_id' => $template?->id,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fromTemplate(ThreadsPostType $type): ?array
    {
        $template = ThreadsTemplate::query()->nextInTurn($type)->first();

        if (! $template) {
            return null;
        }

        $template->markUsed();

        return [
            'type' => $type,
            'text' => $this->fit($template->body, []),
            'poll_options' => $template->poll_options ?: null,
            'topic_tag' => $template->topic_tag ?: $this->settings->topicTag(),
            'threads_template_id' => $template->id,
        ];
    }

    /**
     * The template filled in and kept under Threads' limit: first by dropping
     * the one placeholder that can go (a tagline, a summary), then by cutting.
     *
     * @param  array<string, string>  $values
     */
    private function fit(string $body, array $values, ?string $droppable = null): string
    {
        $text = trim(strtr($body, $values));

        if (strlen($text) > ThreadsPost::MAX_TEXT_BYTES && $droppable !== null) {
            $text = trim(preg_replace("/\n{3,}/", "\n\n", strtr($body, [...$values, $droppable => ''])) ?? $text);
        }

        if (strlen($text) > ThreadsPost::MAX_TEXT_BYTES) {
            $text = rtrim(mb_strcut($text, 0, ThreadsPost::MAX_TEXT_BYTES - 3)).'…';
        }

        return $text;
    }

    private function tracked(string $url): string
    {
        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query(self::UTM);
    }

    /**
     * A JPEG copy of an image on the public disk, at most 1440px wide: Threads
     * takes no WebP and scales anything wider down anyway. Null when the copy
     * cannot be made, and the post goes out as text with the link preview.
     */
    private function jpegCopy(string $path): ?string
    {
        try {
            $contents = Storage::disk('public')->get($path);

            if (! is_string($contents) || $contents === '') {
                return null;
            }

            return $this->images->storeContents($contents, 'threads', maxDimension: 1440, quality: 85, format: 'jpeg');
        } catch (Throwable $exception) {
            Log::warning('Could not copy a vendor cover for Threads.', ['path' => $path, 'error' => $exception->getMessage()]);

            return null;
        }
    }
}
