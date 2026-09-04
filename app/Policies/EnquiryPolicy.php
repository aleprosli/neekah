<?php

namespace App\Policies;

use App\Models\Enquiry;
use App\Models\User;

class EnquiryPolicy
{
    public function view(User $user, Enquiry $enquiry): bool
    {
        return $user->isAdmin()
            || $enquiry->user_id === $user->id
            || ($user->isVendor() && $user->vendor?->id === $enquiry->vendor_id);
    }

    public function reply(User $user, Enquiry $enquiry): bool
    {
        return $user->isVendor() && $user->vendor?->id === $enquiry->vendor_id;
    }
}
