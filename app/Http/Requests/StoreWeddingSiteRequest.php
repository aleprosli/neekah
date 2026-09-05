<?php

namespace App\Http\Requests;

use App\Models\WeddingSite;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWeddingSiteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('subdomain')) {
            $this->merge(['subdomain' => mb_strtolower(trim($this->string('subdomain')->toString()))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $site = $this->route('wedding')->site;

        return [
            'subdomain' => [
                'required', 'string', 'min:3', 'max:63',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::notIn(WeddingSite::RESERVED_SUBDOMAINS),
                Rule::unique(WeddingSite::class, 'subdomain')->ignore($site),
            ],
            'template' => ['required', Rule::in(array_keys(WeddingSite::TEMPLATES))],
            'bride_name' => ['required', 'string', 'max:80'],
            'groom_name' => ['required', 'string', 'max:80'],
            'bride_parents' => ['nullable', 'string', 'max:160'],
            'groom_parents' => ['nullable', 'string', 'max:160'],
            'salutation' => ['nullable', 'string', 'max:200'],
            'invitation_note' => ['nullable', 'string', 'max:1000'],
            'event_date' => ['required', 'date'],
            'starts_at' => ['nullable', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'venue_name' => ['nullable', 'string', 'max:160'],
            'venue_address' => ['nullable', 'string', 'max:500'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'itinerary' => ['nullable', 'array', 'max:12'],
            'itinerary.*.time' => ['nullable', 'string', 'max:20'],
            'itinerary.*.label' => ['nullable', 'string', 'max:80'],
            'contacts' => ['nullable', 'array', 'max:6'],
            'contacts.*.name' => ['nullable', 'string', 'max:80'],
            'contacts.*.phone' => ['nullable', 'string', 'max:30'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'rsvp_enabled' => ['nullable', 'boolean'],
            'rsvp_deadline' => ['nullable', 'date', 'before_or_equal:event_date'],
            'closing_note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Drop empty itinerary and contact rows so blank inputs are not stored.
     *
     * @return array<string, mixed>
     */
    public function siteAttributes(): array
    {
        $data = $this->safe()->except('cover_image');

        $data['itinerary'] = collect($data['itinerary'] ?? [])
            ->filter(fn (array $row): bool => filled($row['time'] ?? null) && filled($row['label'] ?? null))
            ->map(fn (array $row): array => ['time' => $row['time'], 'label' => $row['label']])
            ->values()
            ->all();

        $data['contacts'] = collect($data['contacts'] ?? [])
            ->filter(fn (array $row): bool => filled($row['name'] ?? null) && filled($row['phone'] ?? null))
            ->map(fn (array $row): array => ['name' => $row['name'], 'phone' => $row['phone']])
            ->values()
            ->all();

        $data['rsvp_enabled'] = $this->boolean('rsvp_enabled');

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'subdomain' => 'alamat web',
            'template' => 'template',
            'bride_name' => 'nama pengantin perempuan',
            'groom_name' => 'nama pengantin lelaki',
            'event_date' => 'tarikh majlis',
            'starts_at' => 'masa mula',
            'ends_at' => 'masa tamat',
            'map_url' => 'pautan peta',
            'cover_image' => 'gambar utama',
            'rsvp_deadline' => 'tarikh akhir RSVP',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'subdomain.regex' => 'Alamat web hanya boleh mengandungi huruf kecil, nombor dan tanda sengkang.',
            'subdomain.not_in' => 'Alamat web ini dikhaskan untuk platform. Sila pilih yang lain.',
            'subdomain.unique' => 'Alamat web ini telah diambil. Cuba yang lain.',
        ];
    }
}
