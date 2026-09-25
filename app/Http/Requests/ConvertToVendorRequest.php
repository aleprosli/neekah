<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Support\PhoneNumber;
use App\Support\States;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Propaganistas\LaravelPhone\Rules\Phone;

class ConvertToVendorRequest extends FormRequest
{
    /**
     * The owner switching themselves, or an admin switching {user} for them.
     */
    public function authorize(): bool
    {
        $user = $this->route('user');

        return $user instanceof User
            ? $this->user()->can('switchToVendor', $user)
            : $this->user()->canBecomeVendor();
    }

    /**
     * The business half of RegisterVendorRequest; the account already exists.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', 'max:30', (new Phone)->international()->country('MY')],
            'business_name' => ['required', 'string', 'max:120', Rule::unique(Vendor::class, 'name')],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'max:80'],
            'district' => ['required', 'string', Rule::in(States::districts($this->input('state')))],
            'state' => ['required', Rule::in(States::names())],
            'tagline' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * Store the number the way wa.me and every other country reads it. One
     * that cannot be read is left as typed, for the phone rule to refuse.
     */
    protected function prepareForValidation(): void
    {
        if (filled($phone = PhoneNumber::toE164($this->string('phone')->toString()))) {
            $this->merge(['phone' => $phone]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => __('fields.nombor_telefon'),
            'business_name' => __('fields.nama_perniagaan'),
            'category_id' => __('fields.kategori'),
            'city' => __('fields.bandar'),
            'district' => __('fields.daerah'),
            'state' => __('fields.negeri'),
            'tagline' => __('fields.tagline'),
        ];
    }
}
