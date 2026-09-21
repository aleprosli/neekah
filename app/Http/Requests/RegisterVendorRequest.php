<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Rules\Turnstile;
use App\Support\States;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class RegisterVendorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')->whereNot('role', UserRole::Customer->value)],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'business_name' => ['required', 'string', 'max:120', Rule::unique(Vendor::class, 'name')],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::in(States::names())],
            'tagline' => ['nullable', 'string', 'max:160'],
            'cf-turnstile-response' => [app(Turnstile::class)],
        ];
    }

    /**
     * A couple who is really a vendor gets told how to switch, not just that
     * the email is taken — otherwise they are stuck with no way forward.
     *
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $isCoupleAccount = User::where('email', $this->input('email'))
                    ->where('role', UserRole::Customer)
                    ->exists();

                if ($isCoupleAccount) {
                    $validator->errors()->add('existing_customer', 'Emel ini sudah didaftar sebagai akaun pengantin. Log masuk dengan akaun itu untuk menukarnya ke akaun vendor.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('fields.nama'),
            'email' => __('fields.emel'),
            'phone' => __('fields.nombor_telefon'),
            'password' => __('fields.kata_laluan'),
            'business_name' => __('fields.nama_perniagaan'),
            'category_id' => __('fields.kategori'),
            'city' => __('fields.bandar'),
            'state' => __('fields.negeri'),
            'tagline' => __('fields.tagline'),
        ];
    }
}
