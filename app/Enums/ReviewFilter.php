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
            self::Reported => 'Dilaporkan vendor',
            self::Hidden => 'Disembunyikan',
            self::Open => 'Review terbuka',
            self::Verified => 'Dari tempahan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Reported => 'Vendor membantah review ini dan meminta admin melihatnya. Ia masih dipaparkan sehingga anda bertindak.',
            self::Hidden => 'Sudah ditarik dari profil vendor. Rekodnya kekal, termasuk sebab dan siapa yang menariknya.',
            self::Open => 'Ditulis terus pada profil, tanpa tempahan. Tidak menyentuh rating, mata atau ranking vendor.',
            self::Verified => 'Datang daripada tempahan yang selesai di Neekah. Hanya yang ini menggerakkan rating dan ranking.',
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
