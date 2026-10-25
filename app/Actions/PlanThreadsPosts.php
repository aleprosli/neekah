<?php

namespace App\Actions;

use App\Enums\ThreadsPostStatus;
use App\Enums\ThreadsPostType;
use App\Models\ThreadsPost;
use App\Support\Locales;
use App\Support\Threads\ThreadsComposer;
use App\Support\ThreadsSettings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Traits\Localizable;

/**
 * Fill every empty daily slot from now to ThreadsSettings::daysAhead() with a
 * post written by ThreadsComposer. A slot is filled once: the post keeps its
 * slot_at even when an admin moves its time or cancels it, so nothing an
 * admin decided is planned over.
 *
 * A slot whose type has nothing to write from (no blog post left to share, an
 * empty template bank) gets a vendor listing instead, and is left empty only
 * when there is no vendor either.
 */
class PlanThreadsPosts
{
    use Localizable;

    /** A slot closer than this is left alone: it would go out before anyone could look at it. */
    public const LEAD_MINUTES = 5;

    public function __construct(private ThreadsSettings $settings, private ThreadsComposer $composer, private StoreOptimizedImage $images) {}

    /**
     * @return int How many posts were planned.
     */
    public function handle(): int
    {
        // The nightly run and an admin's button must not both fill the same slot.
        return (int) Cache::lock('threads-plan', 120)->get(
            fn (): int => $this->withLocale(Locales::DEFAULT, fn (): int => $this->fill()),
        );
    }

    /**
     * Write a planned post again from the next vendor or template in turn,
     * for an admin who does not like the one they were given.
     *
     * @return bool False when there is nothing else of that type to write from.
     */
    public function rewrite(ThreadsPost $post): bool
    {
        $attributes = $this->withLocale(Locales::DEFAULT, fn (): ?array => $this->composer->compose($post->type));

        if ($attributes === null) {
            return false;
        }

        $this->images->delete($post->image_path);

        $post->update([
            'image_path' => null,
            'image_url' => null,
            'link_url' => null,
            'topic_tag' => null,
            'poll_options' => null,
            'vendor_id' => null,
            'post_id' => null,
            'threads_template_id' => null,
            ...$attributes,
        ]);

        return true;
    }

    private function fill(): int
    {
        $slots = $this->settings->slots();
        $planned = 0;

        foreach (range(0, $this->settings->daysAhead() - 1) as $offset) {
            $day = today()->addDays($offset);

            foreach ($slots as $slot) {
                $at = $day->copy()->setTimeFromTimeString($slot['time']);

                if ($at->lt(now()->addMinutes(self::LEAD_MINUTES)) || ThreadsPost::where('slot_at', $at)->exists()) {
                    continue;
                }

                $attributes = $this->composer->compose($slot['type'])
                    ?? ($slot['type'] !== ThreadsPostType::Listing ? $this->composer->compose(ThreadsPostType::Listing) : null);

                if ($attributes === null) {
                    continue;
                }

                ThreadsPost::create([
                    ...$attributes,
                    'status' => ThreadsPostStatus::Scheduled,
                    'scheduled_at' => $at,
                    'slot_at' => $at,
                ]);

                $planned++;
            }
        }

        return $planned;
    }
}
