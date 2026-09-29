<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Counts a couple reaching for a vendor's WhatsApp or phone, for the vendor's
 * analytics. The vendor looking at their own page and an admin checking it are
 * not leads, so neither is counted.
 */
class VendorContactController extends Controller
{
    public function whatsapp(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $url = $vendor->whatsappUrl(__('pages.profile.whatsapp_greeting', ['name' => $vendor->name]));
        abort_unless($url !== null, 404);

        if ($this->counts($request->user(), $vendor)) {
            $vendor->recordStat('whatsapp_clicks');
        }

        return redirect()->away($url);
    }

    public function phone(Request $request, Vendor $vendor): Response
    {
        abort_unless($vendor->isApproved() && filled($vendor->phone), 404);

        if ($this->counts($request->user(), $vendor)) {
            $vendor->recordStat('phone_clicks');
        }

        return response()->noContent();
    }

    private function counts(User $user, Vendor $vendor): bool
    {
        return ! $user->isAdmin() && $user->getKey() !== $vendor->user_id;
    }
}
