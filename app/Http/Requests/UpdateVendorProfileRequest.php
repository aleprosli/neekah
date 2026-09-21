<?php

namespace App\Http\Requests;

use App\Enums\PriceUnit;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\ImageSettings;
use App\Support\SocialLinks;
use App\Support\States;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UpdateVendorProfileRequest extends FormRequest
{
    /** Enough for a vendor who really does several things, short of listing the whole marketplace. */
    public const MAX_CATEGORIES = 5;

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
     * Fold the primary category and home state into the multi-value lists, and
     * turn "@kedai" and pasted addresses into the one link that is stored, so
     * the rules below only ever judge a full address.
     */
    protected function prepareForValidation(): void
    {
        // The primary category and the home state are part of the two lists
        // whether or not the form posted them, so the limit below counts what
        // is really saved.
        $this->merge([
            'category_ids' => collect((array) $this->input('category_ids', []))
                ->push($this->input('category_id'))
                ->filter()
                ->map(fn (mixed $id): int => (int) $id)
                ->unique()
                ->values()
                ->all(),
            'service_states' => collect((array) $this->input('service_states', []))
                ->push($this->input('state'))
                ->filter()
                ->unique()
                ->values()
                ->all(),
        ]);

        if (! is_array($this->input('social_links'))) {
            return;
        }

        $this->merge([
            'social_links' => collect($this->input('social_links'))
                ->map(fn (mixed $value, string $platform): ?string => is_string($value) ? SocialLinks::normalise($platform, $value) : null)
                ->all(),
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(ImageSettings $images): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique(Vendor::class, 'name')->ignore($this->user()->vendor)],
            'category_id' => ['required', Rule::exists(Category::class, 'id')->where('is_active', true)],
            'category_ids' => ['nullable', 'array', 'max:'.self::MAX_CATEGORIES],
            'category_ids.*' => [Rule::exists(Category::class, 'id')->where('is_active', true)],
            'tagline' => ['nullable', 'string', 'max:160'],
            'description' => ['nullable', 'string', 'max:2000'],
            'city' => ['required', 'string', 'max:80'],
            'state' => ['required', Rule::in(States::names())],
            'service_states' => ['nullable', 'array'],
            'service_states.*' => [Rule::in(States::names())],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'social_links' => ['nullable', 'array:'.implode(',', array_keys(SocialLinks::PLATFORMS))],
            'social_links.*' => ['nullable', 'string', 'max:255', 'url:https', function (string $attribute, mixed $value, Closure $fail): void {
                $platform = Str::after($attribute, 'social_links.');

                if (! SocialLinks::isAllowed($platform, (string) $value)) {
                    $fail($platform === 'website'
                        ? 'Pautan laman web tidak boleh menghala ke WhatsApp atau Telegram.'
                        : 'Pautan ini bukan pautan '.SocialLinks::PLATFORMS[$platform]['label'].' yang sah.');
                }
            }],
            'price_from' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'price_unit' => ['required', Rule::enum(PriceUnit::class)],
            'cover_tone' => ['required', Rule::in(self::TONES)],
            'cover_image' => ['nullable', ...$images->uploadRules()],
            'logo' => ['nullable', ...$images->uploadRules()],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nama perniagaan',
            'category_id' => 'kategori utama',
            'category_ids' => 'kategori',
            'city' => 'bandar',
            'state' => 'negeri asal',
            'service_states' => 'negeri yang dicover',
            'price_from' => 'harga bermula',
            'price_unit' => 'unit harga',
            'cover_tone' => 'warna',
            'cover_image' => 'gambar muka depan',
            'logo' => 'logo perniagaan',
            ...collect(SocialLinks::PLATFORMS)->mapWithKeys(fn (array $details, string $platform): array => ['social_links.'.$platform => 'pautan '.$details['label']])->all(),
        ];
    }
}
