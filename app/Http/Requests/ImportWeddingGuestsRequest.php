<?php

namespace App\Http\Requests;

use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ImportWeddingGuestsRequest extends FormRequest
{
    /**
     * The list already exists in a spreadsheet or a WhatsApp message, so the
     * import accepts a paste rather than demanding a file.
     */
    public const MAX_LINES = 500;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('wedding')) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'rows' => ['required', 'string', 'max:60000'],
            'side' => ['required', Rule::enum(GuestSide::class)],
            'group' => ['required', Rule::enum(GuestGroup::class)],
        ];
    }

    /**
     * @return array<int, array{line: int, values: array<int, string>}>
     */
    public function lines(): array
    {
        $lines = preg_split('/\R/', $this->string('rows')->toString()) ?: [];
        $parsed = [];

        foreach ($lines as $index => $line) {
            if (trim($line) === '') {
                continue;
            }

            $parsed[] = ['line' => $index + 1, 'values' => array_map(trim(...), str_getcsv($line, ',', '"', '\\'))];

            if (count($parsed) >= self::MAX_LINES) {
                break;
            }
        }

        return $parsed;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'rows' => 'senarai tetamu',
            'side' => 'pihak',
            'group' => 'kumpulan',
        ];
    }
}
