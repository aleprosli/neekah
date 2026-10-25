<?php

namespace App\Http\Controllers\Api\Partner;

use App\Http\Controllers\Controller;
use App\Http\Middleware\EnsurePartnerKey;
use App\Models\Vendor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VendorContactController extends Controller
{
    /**
     * A player tapping WhatsApp on a partner's site. The link is signed, not
     * keyed, because it opens in the player's own browser; it counts the tap
     * in the vendor's analytics like one on Neekah, then hands over to
     * WhatsApp with a greeting that says where they were found.
     */
    public function whatsapp(Request $request, Vendor $vendor): RedirectResponse
    {
        abort_unless($vendor->isApproved(), 404);

        $partner = EnsurePartnerKey::nameOf((string) $request->query('partner'));

        $url = $vendor->whatsappUrl(__('pages.profile.partner_whatsapp_greeting', ['name' => $vendor->name, 'partner' => $partner]));
        abort_unless($url !== null, 404);

        $vendor->recordStat('whatsapp_clicks');

        return redirect()->away($url);
    }
}
