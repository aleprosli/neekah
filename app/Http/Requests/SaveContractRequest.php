<?php

namespace App\Http\Requests;

use App\Models\Contract;
use App\Models\Quotation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * A contract as the vendor writes it: who it is with, and its sections. Only
 * the vendor's own quotation can be attached.
 */
class SaveContractRequest extends FormRequest
{
    public function authorize(): bool
    {
        $contract = $this->route('contract');

        return $contract instanceof Contract
            ? ($this->user()?->can('update', $contract) ?? false)
            : ($this->user()?->can('update', $this->user()->vendor) ?? false);
    }

    /**
     * Sections the vendor left empty are dropped rather than refused.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'sections' => collect($this->input('sections', []))
                ->filter(fn ($section): bool => is_array($section) && (filled($section['title'] ?? null) || filled($section['body'] ?? null)))
                ->values()
                ->all(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'client_name' => ['required', 'string', 'max:120'],
            'client_phone' => ['nullable', 'string', 'max:30'],
            'client_email' => ['nullable', 'email', 'max:255'],
            'event_date' => ['nullable', 'date'],
            'quotation_id' => ['nullable', 'integer', Rule::exists(Quotation::class, 'id')->where('vendor_id', $this->user()->vendor->id)],
            'save_as_default' => ['sometimes', 'boolean'],
            'sections' => ['required', 'array', 'min:1', 'max:'.Contract::MAX_SECTIONS],
            'sections.*.key' => ['nullable', 'string', 'max:40'],
            'sections.*.title' => ['required', 'string', 'max:150'],
            'sections.*.body' => ['required', 'string', 'max:10000'],
        ];
    }

    /**
     * What SaveContract takes.
     *
     * @return array<string, mixed>
     */
    public function contractData(): array
    {
        return collect($this->validated())->except('save_as_default')->all();
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
            'sections' => __('fields.bahagian_kontrak'),
            'sections.*.title' => __('fields.tajuk_bahagian'),
            'sections.*.body' => __('fields.isi_bahagian'),
        ];
    }
}
