<?php

namespace App\Models;

use App\Enums\AnnouncementAudience;
use App\Enums\AnnouncementStatus;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * Something the platform wants to tell its users: written once by an admin,
 * then delivered to every recipient by email and to their notification bell.
 */
#[Fillable(['user_id', 'audience', 'subject', 'body', 'custom_emails', 'action_label', 'action_url', 'status', 'recipients_count', 'sent_at'])]
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
            'custom_emails' => 'array',
            'status' => AnnouncementStatus::class,
            'sent_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** The accounts an admin picked by hand, for a Custom announcement. */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /**
     * The accounts this announcement goes to. A hand-picked list is whatever
     * was attached; every other audience is a query over the whole user table.
     *
     * @return Builder<User>
     */
    public function recipientQuery(): Builder
    {
        if (! $this->audience->isCustom()) {
            return $this->audience->recipients();
        }

        return User::query()
            ->whereNull('deactivated_at')
            ->whereIn('id', $this->users()->pluck('users.id'));
    }

    /**
     * Addresses typed in by hand that belong to no account. They are mailed
     * on demand, so they get the email and nothing in a notification bell.
     *
     * @return array<int, string>
     */
    public function addressesWithoutAccounts(): array
    {
        $emails = collect($this->custom_emails ?? [])->map(fn (string $email): string => Str::lower($email));

        return $emails
            ->diff(User::whereIn('email', $emails)->pluck('email')->map(fn (string $email): string => Str::lower($email)))
            ->values()
            ->all();
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
