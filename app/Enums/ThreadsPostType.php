<?php

namespace App\Enums;

/**
 * What a Threads post is for. Each daily slot names one, and the planner
 * writes the post from where that kind comes from: a vendor's page, a blog
 * post, or the template bank.
 */
enum ThreadsPostType: string
{
    /** One approved vendor, with their cover image and a link to their page. */
    case Listing = 'listing';

    /** A question or a poll, to get people replying. */
    case Engagement = 'engagement';

    /** Practical advice for a couple planning their majlis. */
    case Tip = 'tip';

    /** A blog post, shared with its link preview. */
    case Blog = 'blog';

    /** Something Neekah itself offers: the checklist, the digital card, Kenangan. */
    case Promo = 'promo';

    public function label(): string
    {
        return match ($this) {
            self::Listing => __('enums.threads_post_type.listing'),
            self::Engagement => __('enums.threads_post_type.engagement'),
            self::Tip => __('enums.threads_post_type.tip'),
            self::Blog => __('enums.threads_post_type.blog'),
            self::Promo => __('enums.threads_post_type.promo'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Listing => __('enums.threads_post_type_desc.listing'),
            self::Engagement => __('enums.threads_post_type_desc.engagement'),
            self::Tip => __('enums.threads_post_type_desc.tip'),
            self::Blog => __('enums.threads_post_type_desc.blog'),
            self::Promo => __('enums.threads_post_type_desc.promo'),
        };
    }

    /** The palette key the badge components use, in Blade and in Vue. */
    public function tone(): string
    {
        return match ($this) {
            self::Listing => 'brand',
            self::Engagement => 'sky',
            self::Tip => 'emerald',
            self::Blog => 'amber',
            self::Promo => 'muted',
        };
    }

    /**
     * The placeholders a template of this type may use, each replaced with
     * the vendor's or the blog post's own value when the post is written.
     *
     * @return list<string>
     */
    public function placeholders(): array
    {
        return match ($this) {
            self::Listing => ['{nama}', '{kategori}', '{negeri}', '{tagline}', '{harga}', '{pautan}'],
            self::Blog => ['{tajuk}', '{ringkasan}', '{pautan}'],
            default => [],
        };
    }
}
