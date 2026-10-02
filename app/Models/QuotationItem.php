<?php

namespace App\Models;

use App\Enums\QuotationItemKind;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of a quotation, copied from the package (or typed in as an add-on)
 * when the quotation was saved.
 */
#[Fillable(['quotation_id', 'package_id', 'kind', 'name', 'description', 'features', 'quantity', 'unit_price', 'line_total', 'sort_order'])]
class QuotationItem extends Model
{
    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => QuotationItemKind::class,
            'features' => 'array',
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }
}
