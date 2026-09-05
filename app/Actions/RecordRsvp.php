<?php

namespace App\Actions;

use App\Models\WeddingGuest;
use App\Models\WeddingRsvp;
use App\Models\WeddingSite;
use App\Support\PhoneNumber;

class RecordRsvp
{
    /**
     * Store a reply and attach it to a named guest where we can honestly say so.
     *
     * @param  array{name: string, phone: ?string, attending: bool, pax: int, message: ?string}  $data
     */
    public function handle(WeddingSite $site, array $data, ?string $token = null): WeddingRsvp
    {
        $guest = $this->guestForToken($site, $token);

        if ($guest) {
            return $this->attachTo($site, $guest, $data, 'token');
        }

        $phone = PhoneNumber::normalise($data['phone'] ?? null);

        if ($phone !== null) {
            $existing = $site->rsvps()->where('phone_normalised', $phone)->whereNull('wedding_guest_id')->first();

            if ($existing) {
                $existing->update($data);

                return $existing;
            }

            $matched = $this->soleGuestWithPhone($site, $phone);

            if ($matched) {
                return $this->attachTo($site, $matched, $data, 'phone');
            }
        }

        return $site->rsvps()->create($data);
    }

    /**
     * A guest's own link is proof of identity, so a second reply updates the
     * first rather than adding a row that would be counted twice.
     *
     * @param  array<string, mixed>  $data
     */
    private function attachTo(WeddingSite $site, WeddingGuest $guest, array $data, string $matchedBy): WeddingRsvp
    {
        return WeddingRsvp::updateOrCreate(
            ['wedding_guest_id' => $guest->id],
            $data + ['wedding_site_id' => $site->id, 'matched_by' => $matchedBy],
        );
    }

    private function guestForToken(WeddingSite $site, ?string $token): ?WeddingGuest
    {
        if (blank($token)) {
            return null;
        }

        return WeddingGuest::query()
            ->where('token', $token)
            ->where('wedding_id', $site->wedding_id)
            ->first();
    }

    /**
     * Phone is the only guest field with enough entropy to match on, and even
     * then only when exactly one person carries it. Names are never matched:
     * a guest list full of "Aina" and "Abang Mie" would bind the wrong reply
     * to the wrong person and quietly corrupt the caterer's number.
     */
    private function soleGuestWithPhone(WeddingSite $site, string $phone): ?WeddingGuest
    {
        $matches = WeddingGuest::query()
            ->where('wedding_id', $site->wedding_id)
            ->where('phone_normalised', $phone)
            ->whereDoesntHave('rsvp')
            ->limit(2)
            ->get();

        return $matches->count() === 1 ? $matches->first() : null;
    }
}
