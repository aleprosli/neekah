<?php

namespace App\Models;

use App\Enums\WeddingRole;
use Database\Factories\WeddingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['user_id', 'title', 'event_date', 'city', 'state', 'budget', 'notes'])]
class Wedding extends Model
{
    /** @use HasFactory<WeddingFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'event_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    /**
     * The person who created the project. Also present in members() as the owner.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wedding_members')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WeddingInvitation::class);
    }

    /**
     * The invited half of the couple, if they have joined.
     */
    public function partner(): ?User
    {
        return $this->members->firstWhere('pivot.role', WeddingRole::Partner->value);
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->user_id === $user->id;
    }

    public function hasMember(User $user): bool
    {
        return $this->members()->whereKey($user->getKey())->exists();
    }

    public function isFull(): bool
    {
        return $this->members()->count() >= 2;
    }

    public function addMember(User $user, WeddingRole $role = WeddingRole::Partner): void
    {
        $this->members()->syncWithoutDetaching([$user->id => ['role' => $role->value]]);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function enquiries(): HasMany
    {
        return $this->hasMany(Enquiry::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(WeddingTask::class)->orderBy('sort_order')->orderBy('due_date');
    }

    public function budgetItems(): HasMany
    {
        return $this->hasMany(WeddingBudgetItem::class);
    }

    public function site(): HasOne
    {
        return $this->hasOne(WeddingSite::class);
    }

    public function timelineItems(): HasMany
    {
        return $this->hasMany(WeddingTimelineItem::class)->orderBy('starts_at');
    }
}
