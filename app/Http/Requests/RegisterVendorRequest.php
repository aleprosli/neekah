<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

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
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
            'phone' => ['required', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'business_name' => ['required', 'string', 'max:120', Rule::unique(Vendor::class, 'name')],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::in(Vendor::STATES)],
            'tagline' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'emel',
            'phone' => 'nombor telefon',
            'password' => 'kata laluan',
            'business_name' => 'nama perniagaan',
            'category_id' => 'kategori',
            'city' => 'bandar',
            'state' => 'negeri',
            'tagline' => 'tagline',
        ];
    }
}
