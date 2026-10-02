<?php

namespace App\Http\Requests;

use App\Enums\DepositType;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A quotation as the vendor writes it. Only the vendor's own packages and
 * enquiries can be named; the totals are not accepted from the form at all.
 */
class SaveQuotationRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quotation = $this->route('quotation');

        return $quotation instanceof Quotation
            ? ($this->user()?->can('update', $quotation) ?? false)
            : ($this->user()?->can('update', $this->user()->vendor) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $vendorId = $this->user()->vendor->id;

        return [
            'client_name' => ['required', 'string', 'max:120'],
            'client_phone' => ['nullable', 'string', 'max:30'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'event_location' => ['nullable', 'string', 'max:255'],
            'valid_until' => ['required', 'date', 'after_or_equal:today'],
            'discount_type' => ['required', Rule::enum(DepositType::class)],
            'discount_value' => ['nullable', 'numeric', 'min:0', 'max:9999999', Rule::when($this->input('discount_type') === DepositType::Percent->value, ['max:100'])],
            'deposit_type' => ['required', Rule::enum(DepositType::class)],
            'deposit_value' => ['nullable', 'numeric', 'min:0', 'max:9999999', Rule::when($this->input('deposit_type') === DepositType::Percent->value, ['max:100'])],
            'terms' => ['nullable', 'string', 'max:5000'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'save_terms_as_default' => ['sometimes', 'boolean'],
            'enquiry_id' => ['nullable', 'integer', Rule::exists(Enquiry::class, 'id')->where('vendor_id', $vendorId)],
            'items' => ['required', 'array', 'min:1', 'max:'.Quotation::MAX_ITEMS],
            'items.*.package_id' => ['nullable', 'integer', Rule::exists(Package::class, 'id')->where('vendor_id', $vendorId)],
            'items.*.name' => ['required_without:items.*.package_id', 'nullable', 'string', 'max:150'],
            'items.*.description' => ['nullable', 'string', 'max:500'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:999'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0', 'max:9999999'],
        ];
    }

    /**
     * What SaveQuotation takes.
     *
     * @return array<string, mixed>
     */
    public function quotationData(): array
    {
        return collect($this->validated())->except('save_terms_as_default')->all();
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'client_name' => __('fields.nama_klien'),
            'client_phone' => __('fields.telefon_klien'),
            'client_email' => __('fields.emel_klien'),
            'event_date' => __('fields.tarikh_majlis'),
            'event_location' => __('fields.lokasi_majlis'),
            'valid_until' => __('fields.sah_sehingga'),
            'discount_value' => __('fields.diskaun'),
            'deposit_value' => __('fields.deposit'),
            'terms' => __('fields.terma_syarat'),
            'notes' => __('fields.nota'),
            'items' => __('fields.item_sebut_harga'),
            'items.*.name' => __('fields.nama_item'),
            'items.*.quantity' => __('fields.kuantiti'),
            'items.*.unit_price' => __('fields.harga_seunit'),
        ];
    }
}
