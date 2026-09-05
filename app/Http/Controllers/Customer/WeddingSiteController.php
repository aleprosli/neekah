<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingSiteRequest;
use App\Models\SiteTemplate;
use App\Models\Wedding;
use App\Models\WeddingSite;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WeddingSiteController extends Controller
{
    /**
     * The invitation editor. A wedding without a site yet gets a draft filled in
     * from the wedding project, so the couple starts with something to preview.
     */
    public function edit(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $site = $wedding->site ?? $this->draftFor($wedding);

        // Arriving from a gallery preview preselects that design.
        if ($chosen = $request->string('template')->toString()) {
            $site->template = $chosen;
        }

        return view('customer.site', [
            'wedding' => $wedding,
            'site' => $site,
            'templates' => SiteTemplate::active()->ordered()->get()->groupBy('style'),
            'domain' => config('neekah.site_domain'),
            'rsvpCount' => $wedding->site?->rsvps()->where('attending', true)->sum('pax') ?? 0,
        ]);
    }

    public function update(StoreWeddingSiteRequest $request, Wedding $wedding): RedirectResponse
    {
        $attributes = $request->siteAttributes();

        if ($request->hasFile('cover_image')) {
            if ($wedding->site?->cover_image) {
                Storage::disk('public')->delete($wedding->site->cover_image);
            }

            $attributes['cover_image'] = $request->file('cover_image')->store('sites/'.$wedding->id, 'public');
        }

        $site = $wedding->site()->updateOrCreate([], $attributes);

        return redirect()
            ->route('site.edit')
            ->with('status', $site->is_published
                ? 'Kad jemputan dikemas kini dan sudah tersiar di '.$site->url()
                : 'Kad jemputan disimpan. Tekan "Siarkan" apabila anda sudah bersedia.');
    }

    /**
     * Publish or unpublish the invitation.
     */
    public function publish(Request $request, Wedding $wedding): RedirectResponse
    {
        Gate::authorize('update', $wedding);

        $site = $wedding->site;
        abort_unless($site !== null, 404);

        $site->update(['is_published' => $request->boolean('published')]);

        return back()->with('status', $site->is_published
            ? 'Kad jemputan anda kini tersiar di '.$site->url()
            : 'Kad jemputan ditarik daripada paparan awam.');
    }

    /**
     * A live preview of the couple's own content, without publishing it.
     */
    public function preview(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $site = $wedding->site ?? $this->draftFor($wedding);

        return view('sites.show', [
            'site' => $site,
            'template' => $site->design(),
            'preview' => true,
        ]);
    }

    /**
     * A draft built from the wedding project so the editor is never empty.
     */
    private function draftFor(Wedding $wedding): WeddingSite
    {
        [$bride, $groom] = array_pad(preg_split('/\s*&\s*/', $wedding->title, 2) ?: [], 2, '');

        return new WeddingSite([
            'wedding_id' => $wedding->id,
            'subdomain' => Str::slug($wedding->title) ?: 'majlis-'.$wedding->id,
            'template' => SiteTemplate::active()->ordered()->value('slug') ?? 'seri-gangsa',
            'bride_name' => $bride ?: 'Pengantin Perempuan',
            'groom_name' => $groom ?: 'Pengantin Lelaki',
            'event_date' => $wedding->event_date,
            'starts_at' => '11:00',
            'ends_at' => '16:00',
            'venue_name' => $wedding->city,
            'venue_address' => $wedding->city.', '.$wedding->state,
            'salutation' => 'Dengan penuh kesyukuran, kami menjemput Dato\' / Datin / Tuan / Puan / Encik / Cik ke majlis perkahwinan anakanda kami',
            'itinerary' => [
                ['time' => '11:00 pagi', 'label' => 'Ketibaan tetamu'],
                ['time' => '12:30 tengah hari', 'label' => 'Ketibaan pengantin'],
                ['time' => '1:00 petang', 'label' => 'Makan beradab'],
                ['time' => '4:00 petang', 'label' => 'Majlis bersurai'],
            ],
            'contacts' => [],
            'rsvp_enabled' => true,
            'closing_note' => 'Kehadiran dan doa restu daripada tuan/puan amatlah kami hargai.',
        ]);
    }
}
