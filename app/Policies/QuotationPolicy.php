<?php

namespace App\Policies;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\User;

/**
 * A quotation is its vendor's alone. The client never signs in: they reach
 * it by its token on the public page, which no policy guards.
 */
class QuotationPolicy
{
    public function view(User $user, Quotation $quotation): bool
    {
        return $this->owns($user, $quotation);
    }

    /** Until the client has answered it; after that a copy is the way to revise it. */
    public function update(User $user, Quotation $quotation): bool
    {
        return $this->owns($user, $quotation) && $quotation->status->isEditable();
    }

    /** Only a draft nobody has seen. */
    public function delete(User $user, Quotation $quotation): bool
    {
        return $this->owns($user, $quotation) && $quotation->status === QuotationStatus::Draft;
    }

    public function invoice(User $user, Quotation $quotation): bool
    {
        return $this->owns($user, $quotation) && $quotation->status === QuotationStatus::Accepted;
    }

    private function owns(User $user, Quotation $quotation): bool
    {
        return $user->isVendor() && $user->vendor?->id === $quotation->vendor_id;
    }
}
