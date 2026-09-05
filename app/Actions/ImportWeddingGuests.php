<?php

namespace App\Actions;

use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use App\Models\Wedding;
use App\Models\WeddingGuest;
use App\Support\PhoneNumber;

class ImportWeddingGuests
{
    /**
     * Take pasted lines of "nama, telefon, pihak, kumpulan, pax" and add the
     * valid ones, reporting the rest by line number so the couple can fix
     * exactly what failed instead of re-pasting blind.
     *
     * @param  array<int, array{line: int, values: array<int, string>}>  $rows
     * @return array{imported: int, updated: int, errors: array<int, string>}
     */
    public function handle(Wedding $wedding, array $rows, GuestSide $defaultSide, GuestGroup $defaultGroup): array
    {
        $imported = 0;
        $updated = 0;
        $errors = [];

        foreach ($rows as $row) {
            [$name, $phone, $side, $group, $pax] = array_pad($row['values'], 5, null);

            if (blank($name)) {
                $errors[] = "Baris {$row['line']}: nama tetamu kosong.";

                continue;
            }

            if (mb_strlen($name) > 80) {
                $errors[] = "Baris {$row['line']}: nama tetamu terlalu panjang.";

                continue;
            }

            if (filled($phone) && PhoneNumber::normalise($phone) === null) {
                $errors[] = "Baris {$row['line']}: nombor telefon tidak sah.";

                continue;
            }

            $attributes = [
                'name' => $name,
                'phone' => $phone ?: null,
                'side' => (GuestSide::tryFrom((string) $side) ?? $defaultSide)->value,
                'group' => (GuestGroup::tryFrom((string) $group) ?? $defaultGroup)->value,
                'pax_invited' => max(1, min(20, (int) ($pax ?: 1))),
            ];

            $existing = $this->existingGuest($wedding, $phone);

            if ($existing) {
                $existing->update($attributes);
                $updated++;

                continue;
            }

            $wedding->guests()->create($attributes);
            $imported++;
        }

        return ['imported' => $imported, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * Re-pasting a corrected list must not double the headcount, so a row
     * carrying a phone we already hold updates that guest.
     */
    private function existingGuest(Wedding $wedding, ?string $phone): ?WeddingGuest
    {
        $normalised = PhoneNumber::normalise($phone);

        return $normalised === null
            ? null
            : $wedding->guests()->where('phone_normalised', $normalised)->first();
    }
}
