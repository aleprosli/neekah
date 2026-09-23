<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One vendor, one day, three counters. Nothing about who looked lives here.
 */
#[Fillable(['vendor_id', 'date', 'profile_views', 'whatsapp_clicks', 'phone_clicks'])]
class VendorDailyStat extends Model
{
    /** The counters a visit can move, which is also what the column names are. */
    public const COUNTERS = ['profile_views', 'whatsapp_clicks', 'phone_clicks'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        // A plain date, so a lookup by "2026-09-23" matches the row written;
        // see WeddingSiteView for what the default serialisation did.
        return ['date' => 'date:Y-m-d'];
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
