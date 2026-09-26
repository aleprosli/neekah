<?php

namespace App\Models;

use Database\Factories\VendorUnavailableDateFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['vendor_id', 'date', 'reason', 'source', 'external_uid', 'slots'])]
class VendorUnavailableDate extends Model
{
    /** @use HasFactory<VendorUnavailableDateFactory> */
    use HasFactory;

    /** Closed by the vendor by hand. */
    public const SOURCE_MANUAL = 'manual';

    /** Imported from the vendor's Google Calendar; replaced on every import. */
    public const SOURCE_ICAL = 'ical';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'slots' => 'integer',
        ];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
