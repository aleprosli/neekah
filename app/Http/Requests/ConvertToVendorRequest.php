<?php

namespace App\Http\Requests;

use App\Models\Category;
use App\Models\User;
use App\Models\Vendor;
use App\Support\States;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'phone' => ['required', 'string', 'max:30'],
            'business_name' => ['required', 'string', 'max:120', Rule::unique(Vendor::class, 'name')],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::in(States::names())],
            'tagline' => ['nullable', 'string', 'max:160'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'phone' => 'nombor telefon',
            'business_name' => 'nama perniagaan',
            'category_id' => 'kategori',
            'city' => 'bandar',
            'state' => 'negeri',
            'tagline' => 'tagline',
        ];
    }
}
