<?php

namespace App\Http\Requests;

use App\Enums\AnnouncementAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'subject' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:5000'],
            // A button needs both halves or it is not a button.
            'action_label' => ['nullable', 'required_with:action_url', 'string', 'max:40'],
            'action_url' => ['nullable', 'required_with:action_label', 'url', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'audience' => 'penerima',
            'subject' => 'tajuk',
            'body' => 'isi kandungan',
            'action_label' => 'teks butang',
            'action_url' => 'pautan butang',
        ];
    }
}
