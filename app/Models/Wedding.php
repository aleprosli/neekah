<?php

namespace App\Models;

use App\Actions\ActivateCameraAlbum;
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

    /**
     * Moving the event date moves when each Neekah Kenangan album that
     * follows it is deleted, which is counted from the event. An album with a
     * date of its own (another majlis) keeps its own.
     */
    protected static function booted(): void
    {
        static::updated(function (Wedding $wedding): void {
            if (! $wedding->wasChanged('event_date')) {
                return;
            }

            $wedding->cameraAlbums()
                ->whereNull('event_date')
                ->whereNull('purged_at')
                ->whereNotNull('activated_at')
                ->get()
                ->each(fn (CameraAlbum $album) => $album->update(['expires_at' => ActivateCameraAlbum::expiryFor($wedding->event_date)]));
        });
    }

    /** Neekah Kenangan albums, one per majlis the couple bought one for. */
    public function cameraAlbums(): HasMany
    {
        return $this->hasMany(CameraAlbum::class);
    }

    public function cameraPurchases(): HasMany
    {
        return $this->hasMany(CameraPurchase::class);
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

    public function guests(): HasMany
    {
        return $this->hasMany(WeddingGuest::class)->orderBy('name');
    }

    public function timelineItems(): HasMany
    {
        return $this->hasMany(WeddingTimelineItem::class)->orderBy('starts_at');
    }
}
