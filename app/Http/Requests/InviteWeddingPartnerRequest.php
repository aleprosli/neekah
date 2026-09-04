<?php

namespace App\Http\Requests;

use App\Models\Wedding;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InviteWeddingPartnerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manageMembers', $this->route('wedding')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Wedding $wedding */
                $wedding = $this->route('wedding');
                $email = $this->string('email')->lower()->toString();

                if ($email === mb_strtolower($this->user()->email)) {
                    $validator->errors()->add('email', 'Itu emel anda sendiri.');

                    return;
                }

                if ($wedding->isFull()) {
                    $validator->errors()->add('email', 'Majlis ini sudah ada dua ahli. Buang pasangan sedia ada dahulu.');

                    return;
                }

                if ($wedding->invitations()->pending()->whereRaw('LOWER(email) = ?', [$email])->exists()) {
                    $validator->errors()->add('email', 'Jemputan ke emel ini masih menunggu jawapan.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return ['email' => 'emel pasangan'];
    }
}
