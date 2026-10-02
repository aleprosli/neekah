<?php

namespace App\Actions;

use App\Enums\QuotationItemKind;
use App\Enums\QuotationStatus;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Vendor;
use App\Support\Quotations\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Create or change a quotation and its lines in one go. A package line copies
 * the package's name, description and features as they are today; the price
 * on each line is what the vendor typed, and every sum is worked out again by
 * Quotation::recalculate(), never taken from the form.
 */
class SaveQuotation
{
    /**
     * @param  array{
     *     client_name: string, client_phone?: string|null, client_email?: string|null,
     *     event_date?: string|null, event_location?: string|null, valid_until: string,
     *     discount_type: string, discount_value?: float|string|null, deposit_type: string, deposit_value?: float|string|null,
     *     terms?: string|null, notes?: string|null, enquiry_id?: int|null,
     *     items: list<array{kind: string, package_id?: int|null, name?: string|null, description?: string|null, quantity: int|string, unit_price: float|string}>
     * }  $data
     */
    public function handle(Vendor $vendor, array $data, ?Quotation $quotation = null, bool $saveTermsAsDefault = false): Quotation
    {
        if ($quotation && ! $quotation->status->isEditable()) {
            throw ValidationException::withMessages(['items' => __('validation.custom.quotation_locked')]);
        }

        return DB::transaction(function () use ($vendor, $data, $quotation, $saveTermsAsDefault): Quotation {
            $attributes = [
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'] ?? null,
                'client_email' => $data['client_email'] ?? null,
                'event_date' => $data['event_date'] ?? null,
                'event_location' => $data['event_location'] ?? null,
                'valid_until' => $data['valid_until'],
                'discount_type' => $data['discount_type'],
                'discount_value' => (float) ($data['discount_value'] ?? 0),
                'deposit_type' => $data['deposit_type'],
                'deposit_value' => (float) ($data['deposit_value'] ?? 0),
                'terms' => $data['terms'] ?? null,
                'notes' => $data['notes'] ?? null,
            ];

            if ($quotation) {
                $quotation->update($attributes);
                $quotation->items()->delete();
            } else {
                $quotation = Quotation::create([
                    ...$attributes,
                    'vendor_id' => $vendor->id,
                    'enquiry_id' => $data['enquiry_id'] ?? null,
                    'number' => DocumentNumber::next($vendor, 'QT'),
                    'token' => Quotation::freshToken(),
                    'status' => QuotationStatus::Draft,
                ]);
            }

            $packages = $vendor->packages()->whereKey(collect($data['items'])->pluck('package_id')->filter())->get()->keyBy('id');

            foreach (array_values($data['items']) as $order => $item) {
                $package = isset($item['package_id']) ? $packages->get((int) $item['package_id']) : null;
                $quantity = max(1, (int) $item['quantity']);
                $price = round(max(0, (float) $item['unit_price']), 2);

                $quotation->items()->create([
                    'kind' => $package ? QuotationItemKind::Package : QuotationItemKind::Addon,
                    'package_id' => $package?->id,
                    'name' => $package?->name ?? $item['name'],
                    'description' => $package instanceof Package ? $package->description : ($item['description'] ?? null),
                    'features' => $package?->features,
                    'quantity' => $quantity,
                    'unit_price' => $price,
                    'line_total' => round($price * $quantity, 2),
                    'sort_order' => $order,
                ]);
            }

            $quotation->recalculate();

            if ($saveTermsAsDefault) {
                $vendor->bookingSettings()->updateOrCreate([], ['quotation_terms' => $data['terms'] ?? null]);
            }

            return $quotation->refresh();
        });
    }
}
