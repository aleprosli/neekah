<?php

namespace App\Models;

use App\Enums\PointReason;
use Database\Factories\VendorPointFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['vendor_id', 'reason', 'points', 'pointable_type', 'pointable_id'])]
class VendorPoint extends Model
{
    /** @use HasFactory<VendorPointFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reason' => PointReason::class,
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * The booking, payment or review that earned the points.
     */
    public function pointable(): MorphTo
    {
        return $this->morphTo();
    }
}
