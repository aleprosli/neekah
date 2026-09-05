<?php

namespace App\Http\Requests;

use App\Models\WeddingGuest;
use App\Models\WeddingSite;
use Illuminate\Foundation\Http\FormRequest;

class StoreRsvpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'u' => ['nullable', 'string', 'max:16'],
            'name' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:30'],
            'attending' => ['required', 'boolean'],
            'pax' => ['required_if:attending,1', 'nullable', 'integer', 'between:1,'.$this->paxCeiling()],
            'message' => ['nullable', 'string', 'max:300'],
        ];
    }

    /**
     * A guest replying through their own link may only claim the seats they
     * were given, so one invitation for two cannot book twenty.
     */
    private function paxCeiling(): int
    {
        $guest = $this->invitedGuest();

        return $guest ? max(1, $guest->pax_invited) : 20;
    }

    private function invitedGuest(): ?WeddingGuest
    {
        $token = $this->string('u')->toString();

        if ($token === '') {
            return null;
        }

        $site = WeddingSite::query()->where('subdomain', $this->route('subdomain'))->first();

        return $site
            ? WeddingGuest::query()->where('token', $token)->where('wedding_id', $site->wedding_id)->first()
            : null;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'phone' => 'nombor telefon',
            'attending' => 'kehadiran',
            'pax' => 'bilangan orang',
            'message' => 'ucapan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pax.between' => 'Jemputan anda adalah untuk :max orang. Sila hubungi pengantin jika perlu tambah.',
        ];
    }
}
