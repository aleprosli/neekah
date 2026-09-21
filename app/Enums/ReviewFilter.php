<?php

namespace App\Enums;

use App\Models\Review;
use Illuminate\Database\Eloquent\Builder;

/**
 * The shelves an admin sorts reviews onto. Each owns its own query, so the
 * count on the chip and the rows under it always ask the same question.
 */
enum ReviewFilter: string
{
    case Reported = 'reported';
    case Hidden = 'hidden';
    case Open = 'open';
    case Verified = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::Reported => __('enums.review_filter.reported'),
            self::Hidden => __('enums.review_filter.hidden'),
            self::Open => __('enums.review_filter.open'),
            self::Verified => __('enums.review_filter.verified'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Reported => __('enums.review_filter_desc.reported'),
            self::Hidden => __('enums.review_filter_desc.hidden'),
            self::Open => __('enums.review_filter_desc.open'),
            self::Verified => __('enums.review_filter_desc.verified'),
        };
    }

    /**
     * @param  Builder<Review>  $reviews
     * @return Builder<Review>
     */
    public function apply(Builder $reviews): Builder
    {
        return match ($this) {
            self::Reported => $reviews->whereNotNull('reported_at')->published(),
            self::Hidden => $reviews->whereNotNull('hidden_at'),
            self::Open => $reviews->open(),
            self::Verified => $reviews->verified(),
        };
    }
}
