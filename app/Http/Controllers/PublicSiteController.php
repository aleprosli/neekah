<?php

namespace App\Http\Controllers;

use App\Models\WeddingGuest;
use App\Models\WeddingSite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PublicSiteController extends Controller
{
    /**
     * User agents that fetch a link to build a preview card. They are not a
     * guest opening the invitation, and counting them would tell the couple
     * their guest looked when nobody did.
     *
     * @var array<int, string>
     */
    private const PREVIEW_BOTS = ['whatsapp', 'facebookexternalhit', 'telegrambot', 'twitterbot', 'linkedinbot', 'slackbot', 'discordbot', 'bot', 'crawler', 'spider'];

    /**
     * A published invitation, served from its own subdomain.
     */
    public function __invoke(Request $request, string $subdomain): View
    {
        $site = WeddingSite::query()
            ->published()
            ->with('siteTemplate')
            ->where('subdomain', $subdomain)
            ->firstOr(fn () => abort(404, 'Kad jemputan ini tidak dijumpai.'));

        $site->increment('views');

        $guest = $this->resolveGuest($request, $site);

        return view('sites.show', [
            'site' => $site,
            'template' => $site->design(),
            'preview' => false,
            'guest' => $guest,
        ]);
    }

    /**
     * Resolve the personal token in ?u=, scoped to this card's own wedding.
     *
     * A wrong, stale or guessed token returns null and the ordinary anonymous
     * card renders. Answering differently would turn this into an enumeration
     * oracle that leaks the guest list one name at a time.
     *
     * @return array{name: string, pax_invited: int, token: string, has_responded: bool}|null
     */
    private function resolveGuest(Request $request, WeddingSite $site): ?array
    {
        $token = $request->string('u')->toString();

        if ($token === '') {
            return null;
        }

        $guest = WeddingGuest::query()
            ->where('token', $token)
            ->where('wedding_id', $site->wedding_id)
            ->with('rsvp')
            ->first();

        if (! $guest) {
            return null;
        }

        if (! $this->isPreviewBot($request)) {
            $guest->recordOpen();
        }

        return [
            'name' => $guest->name,
            'pax_invited' => $guest->pax_invited,
            'token' => $guest->token,
            'has_responded' => $guest->rsvp !== null,
        ];
    }

    private function isPreviewBot(Request $request): bool
    {
        return Str::contains(Str::lower((string) $request->userAgent()), self::PREVIEW_BOTS);
    }
}
