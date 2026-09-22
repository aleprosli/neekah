<?php

namespace App\Http\Controllers;

use App\Actions\RenderInvitationPreview;
use App\Models\WeddingSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class InvitationPreviewController extends Controller
{
    /**
     * The image WhatsApp and Facebook show when a card link is shared. Drawn
     * once per version of the card, then handed over as a redirect to wherever
     * it is stored.
     *
     * Redirected rather than streamed through PHP: on an object store, reading
     * the file back to re-send it means the picture crosses the network twice
     * and the origin server pays for both. Every crawler that reads an og:image
     * follows a redirect.
     */
    public function __invoke(RenderInvitationPreview $render, string $subdomain): RedirectResponse
    {
        $site = WeddingSite::query()
            ->published()
            ->with('siteTemplate')
            ->where('subdomain', $subdomain)
            ->firstOr(fn () => abort(404));

        $path = $render->handle($site, $site->design());

        return redirect()
            ->away(Storage::disk('public')->url($path))
            ->withHeaders(['Cache-Control' => 'public, max-age=604800']);
    }
}
