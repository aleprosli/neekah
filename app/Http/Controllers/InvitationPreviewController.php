<?php

namespace App\Http\Controllers;

use App\Actions\RenderInvitationPreview;
use App\Models\WeddingSite;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class InvitationPreviewController extends Controller
{
    /**
     * The image WhatsApp and Facebook show when a card link is shared. Drawn
     * once per version of the card and served from disk after that.
     */
    public function __invoke(RenderInvitationPreview $render, string $subdomain): Response
    {
        $site = WeddingSite::query()
            ->published()
            ->with('siteTemplate')
            ->where('subdomain', $subdomain)
            ->firstOr(fn () => abort(404));

        $path = $render->handle($site, $site->design());

        return response(Storage::disk('public')->get($path), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=604800',
        ]);
    }
}
