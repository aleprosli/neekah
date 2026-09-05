<?php

namespace App\Http\Controllers;

use App\Models\WeddingSite;
use Illuminate\Contracts\View\View;

class PublicSiteController extends Controller
{
    /**
     * A published invitation, served from its own subdomain.
     */
    public function __invoke(string $subdomain): View
    {
        $site = WeddingSite::query()
            ->published()
            ->where('subdomain', $subdomain)
            ->firstOr(fn () => abort(404, 'Kad jemputan ini tidak dijumpai.'));

        $site->increment('views');

        return view('sites.show', ['site' => $site, 'preview' => false]);
    }
}
