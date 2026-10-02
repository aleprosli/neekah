<?php

namespace App\Actions;

use App\Enums\ContractStatus;
use App\Models\Contract;
use App\Models\Vendor;
use App\Support\Quotations\DocumentNumber;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Create or change a draft contract. Only a draft: once sent, the client
 * signs exactly what they were shown, so the text is never edited under them.
 */
class SaveContract
{
    /**
     * @param  array{
     *     client_name: string, client_phone?: string|null, client_email?: string|null, event_date?: string|null,
     *     quotation_id?: int|null, sections: list<array{key?: string|null, title: string, body: string}>
     * }  $data
     */
    public function handle(Vendor $vendor, array $data, ?Contract $contract = null, bool $saveAsDefault = false): Contract
    {
        if ($contract && $contract->status !== ContractStatus::Draft) {
            throw ValidationException::withMessages(['sections' => __('validation.custom.contract_locked')]);
        }

        return DB::transaction(function () use ($vendor, $data, $contract, $saveAsDefault): Contract {
            $sections = array_map(fn (array $section): array => [
                'key' => in_array($section['key'] ?? null, Contract::SECTION_KEYS, true) ? $section['key'] : 'custom-'.Str::lower(Str::random(6)),
                'title' => trim($section['title']),
                'body' => trim($section['body']),
            ], array_values($data['sections']));

            $attributes = [
                'client_name' => $data['client_name'],
                'client_phone' => $data['client_phone'] ?? null,
                'client_email' => $data['client_email'] ?? null,
                'event_date' => $data['event_date'] ?? null,
                'quotation_id' => $data['quotation_id'] ?? null,
                'sections' => $sections,
            ];

            if ($contract) {
                $contract->update($attributes);
            } else {
                $contract = Contract::create([
                    ...$attributes,
                    'vendor_id' => $vendor->id,
                    'number' => DocumentNumber::next($vendor, 'KT'),
                    'token' => Contract::freshToken(),
                    'status' => ContractStatus::Draft,
                ]);
            }

            if ($saveAsDefault) {
                $defaults = collect($sections)
                    ->filter(fn (array $section): bool => in_array($section['key'], Contract::SECTION_KEYS, true))
                    ->mapWithKeys(fn (array $section): array => [$section['key'] => $section['body']])
                    ->all();

                $vendor->bookingSettings()->updateOrCreate([], ['contract_defaults' => $defaults]);
            }

            return $contract;
        });
    }
}
