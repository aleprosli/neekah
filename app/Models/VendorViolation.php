<?php

namespace App\Models;

use App\Enums\ViolationAction;
use App\Enums\ViolationStatus;
use App\Enums\ViolationType;
use Database\Factories\VendorViolationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'vendor_id', 'reported_by', 'booking_id', 'type', 'description', 'status',
    'action', 'offence_number', 'admin_note', 'resolved_by', 'resolved_at',
])]
class VendorViolation extends Model
{
    /** @use HasFactory<VendorViolationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ViolationType::class,
            'status' => ViolationStatus::class,
            'action' => ViolationAction::class,
            'resolved_at' => 'datetime',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    #[Scope]
    protected function open(Builder $query): Builder
    {
        return $query->where('status', ViolationStatus::Open);
    }

    #[Scope]
    protected function upheld(Builder $query): Builder
    {
        return $query->where('status', ViolationStatus::Upheld);
    }

    public function isOpen(): bool
    {
        return $this->status === ViolationStatus::Open;
    }
}
