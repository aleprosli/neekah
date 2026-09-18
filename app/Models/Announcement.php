<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Something the platform wants to tell its users: written once by an admin,
 * then delivered to every recipient by email and to their notification bell.
 */
#[Fillable(['user_id', 'audience', 'subject', 'body', 'action_label', 'action_url', 'status', 'recipients_count', 'sent_at'])]
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'audience' => AnnouncementAudience::class,
            'status' => AnnouncementStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The paragraphs of the body, which is what an email prints as lines.
     *
     * @return array<int, string>
     */
    public function paragraphs(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/\R{2,}/', (string) $this->body) ?: [])));
    }

    public function hasAction(): bool
    {
        return filled($this->action_label) && filled($this->action_url);
    }
}
