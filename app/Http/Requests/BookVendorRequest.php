<?php

namespace App\Http\Requests;

use App\Support\DemoCatalogue;
use Illuminate\Foundation\Http\FormRequest;

class BookVendorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $vendor = app(DemoCatalogue::class)->find($this->route('slug')) ?? abort(404);
        $lastPackageIndex = count($vendor['packages']) - 1;

        return [
            'package' => ['required', 'integer', 'between:0,'.$lastPackageIndex],
            'event_date' => ['required', 'date', 'after:today'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:30'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'package' => 'pakej',
            'event_date' => 'tarikh majlis',
            'name' => 'nama',
            'phone' => 'nombor telefon',
        ];
    }
}
