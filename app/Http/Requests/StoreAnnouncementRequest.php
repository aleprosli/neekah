<?php

namespace App\Http\Requests;

use App\Enums\AnnouncementAudience;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
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
            // A hand-picked list may mix accounts and addresses typed in by hand.
            'user_ids' => ['nullable', 'array', 'max:500'],
            'user_ids.*' => [Rule::exists(User::class, 'id')],
            'emails' => ['nullable', 'string', 'max:5000'],
            // A button needs both halves or it is not a button.
            'action_label' => ['nullable', 'required_with:action_url', 'string', 'max:40'],
            'action_url' => ['nullable', 'required_with:action_label', 'url', 'max:255'],
        ];
    }

    public function after(): array
    {
        return [
            function ($validator): void {
                if ($this->chosenAudience() !== AnnouncementAudience::Custom) {
                    return;
                }

                foreach ($this->typedAddresses() as $address) {
                    if (! filter_var($address, FILTER_VALIDATE_EMAIL)) {
                        $validator->errors()->add('emails', '"'.$address.'" bukan alamat emel yang sah.');
                    }
                }

                // A test send goes to the admin alone, so it needs no recipients yet.
                if ($this->routeIs('admin.announcements.store') && $this->input('user_ids', []) === [] && $this->typedAddresses()->isEmpty()) {
                    $validator->errors()->add('user_ids', 'Pilih sekurang-kurangnya seorang penerima, atau taip satu alamat emel.');
                }
            },
        ];
    }

    public function chosenAudience(): AnnouncementAudience
    {
        return AnnouncementAudience::tryFrom($this->string('audience')->toString()) ?? AnnouncementAudience::Everyone;
    }

    /**
     * The addresses an admin typed, one per line or separated by commas.
     *
     * @return Collection<int, string>
     */
    public function typedAddresses(): Collection
    {
        return Str::of((string) $this->input('emails'))
            ->replace([',', ';'], "\n")
            ->explode("\n")
            ->map(fn (string $address): string => Str::lower(trim($address)))
            ->filter()
            ->unique()
            ->values();
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
            'user_ids' => 'penerima',
            'emails' => 'alamat emel',
            'action_label' => 'teks butang',
            'action_url' => 'pautan butang',
        ];
    }
}
