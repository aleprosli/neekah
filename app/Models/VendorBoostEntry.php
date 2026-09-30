<?php

namespace App\Models;

use App\Enums\BoostTokenReason;
use Database\Factories\VendorBoostEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/** One line of a vendor's boost token history. Written only by GrantBoostTokens. */
#[Fillable(['vendor_id', 'change', 'reason', 'source_type', 'source_id', 'note', 'added_by'])]
class VendorBoostEntry extends Model
{
    /** @use HasFactory<VendorBoostEntryFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'change' => 'integer',
            'reason' => BoostTokenReason::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }
}
