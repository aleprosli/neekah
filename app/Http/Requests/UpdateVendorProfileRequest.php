<?php

namespace App\Http\Requests;

use App\Enums\PriceUnit;
use App\Models\Category;
use App\Models\Vendor;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVendorProfileRequest extends FormRequest
{
    public const TONES = [
        'from-rose-400 to-amber-300', 'from-amber-500 to-orange-300', 'from-fuchsia-400 to-rose-300',
        'from-slate-700 to-slate-400', 'from-pink-400 to-rose-200', 'from-emerald-500 to-teal-300',
        'from-sky-400 to-indigo-300', 'from-violet-500 to-purple-300', 'from-lime-400 to-emerald-300',
        'from-yellow-300 to-amber-200', 'from-cyan-400 to-sky-300', 'from-red-400 to-orange-300',
    ];

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->user()->vendor) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique(Vendor::class, 'name')->ignore($this->user()->vendor)],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::in(Vendor::STATES)],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'price_from' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'price_unit' => ['required', Rule::enum(PriceUnit::class)],
            'cover_tone' => ['required', Rule::in(self::TONES)],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama perniagaan',
            'category_id' => 'kategori',
            'city' => 'bandar',
            'state' => 'negeri',
            'price_from' => 'harga bermula',
            'price_unit' => 'unit harga',
            'cover_tone' => 'warna',
            'cover_image' => 'gambar muka depan',
        ];
    }
}
