<?php

namespace App\Http\Controllers\Customer;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingSiteRequest;
use App\Models\CardMusicTrack;
use App\Models\SiteTemplate;
use App\Models\Wedding;
use App\Models\WeddingSite;
use App\Support\Card\CardProps;
use App\Support\Card\Catalog;
use App\Support\Card\Fonts;
use App\Support\Card\Palettes;
use App\Support\Card\Widgets;
use App\Support\ImageSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class WeddingSiteController extends Controller
{
    /**
     * The invitation editor. A wedding without a site yet gets a draft filled in
     * from the wedding project, so the couple starts with something to preview.
     *
     * The designs themselves are not sent with the page: fifty of them carry about
     * two thousand layers between them, and a couple who never opens the design tab
     * should not download the lot. The chosen design travels with the page so the
     * preview draws at once; the rest arrive from designs() when asked for.
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

        $design = $site->design();
        $images = app(ImageSettings::class);
        $published = $wedding->site;

        return view('customer.site', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'exists' => $site->exists,
                'action' => route('weddings.site.update', $wedding),
                'domain' => config('neekah.site_domain'),
                'subdomainCheckUrl' => route('site.subdomain'),
                'designsUrl' => route('site.designs'),
                'imageHint' => $images->uploadHint('1600 × 1200px atau lebih'),
                'limits' => [
                    'designs' => SiteTemplate::active()->count(),
                    'gallery_url' => route('sites.templates'),
                    'preview_url' => route('site.preview'),
                    // The same ceilings StoreWeddingSiteRequest enforces, so the
                    // form cannot offer a row the server will reject.
                    'itinerary' => 12,
                    'contacts' => 6,
                    'gift_accounts' => 4,
                ],
                'site' => $this->formValues($site),
                'design' => $this->designPayload($design),
                'designIndex' => $this->designIndex(),
                'palettes' => Palettes::presets(),
                'paletteRoles' => Palettes::labels(),
                'fontRoles' => collect(Fonts::ROLES)
                    ->mapWithKeys(fn (string $role): array => [$role => __('pages.card_fonts.'.$role)])
                    ->all(),
                'fontOptions' => Fonts::options(),
                'widgetOptions' => collect(Widgets::all())
                    ->map(fn (string $label, string $key): array => ['key' => $key, 'label' => $label])
                    ->values(),
                'photoSlots' => $this->photoSlotLabels(),
                'tracks' => CardMusicTrack::active()->ordered()->get()
                    ->map(fn (CardMusicTrack $track): array => [
                        'id' => $track->id,
                        'label' => $track->label(),
                        'length' => $track->lengthLabel(),
                        'url' => $track->url(),
                    ])->values(),
                'labels' => __('card'),
                'status' => $site->exists ? [
                    'published' => (bool) $site->is_published,
                    'url' => $site->url(),
                    'views' => number_format($site->views),
                    'rsvp_count' => $published?->confirmedPax() ?? 0,
                    'guests_url' => route('guests.index'),
                    'insights_url' => route('site.insights'),
                    'publish_url' => route('weddings.site.publish', $wedding),
                    'draft_note' => 'Kad anda akan berada di '.$site->subdomain.'.'.config('neekah.site_domain').' selepas disiarkan.',
                ] : null,
                'gallery' => $site->exists ? [
                    'store_url' => route('weddings.site.photos.store', $wedding),
                    'photos' => $site->photos->map(fn ($photo): array => [
                        'url' => $photo->url(),
                        'caption' => $photo->caption,
                        'destroy_url' => route('weddings.site.photos.destroy', [$wedding, $photo]),
                    ])->values(),
                ] : null,
                'wishes' => ($published?->rsvps()->whereNotNull('message')->get() ?? collect())
                    ->map(fn ($wish): array => [
                        'id' => $wish->id,
                        'name' => $wish->name,
                        'message' => $wish->message,
                        'public' => $wish->wishIsPublic(),
                        'update_url' => route('weddings.rsvps.update', [$wedding, $wish]),
                    ])->values(),
                'rsvpSummary' => $published && $published->rsvps()->exists() ? [
                    'count' => $published->rsvps()->count(),
                    'url' => route('guests.index'),
                ] : null,
            ]),
        ]);
    }

    /**
     * The designs, for the editor's design shelf.
     *
     * With `slug` it answers one design's canvases, which is what the live preview
     * draws; otherwise it answers a category's worth of cover thumbnails. Either way
     * the layers are fetched when looked at, never shipped with the editor page.
     */
    public function designs(Request $request): JsonResponse
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        if ($slug = $request->string('slug')->toString()) {
            $design = SiteTemplate::active()->where('slug', $slug)->firstOrFail();

            return response()->json(['design' => $this->designPayload($design)]);
        }

        $category = $request->string('category')->toString();
        $sample = $wedding->site ?? $this->draftFor($wedding);

        $designs = SiteTemplate::active()->ordered()
            ->when($category !== '', fn ($query) => $query->where('category', $category))
            ->get();

        return response()->json([
            'designs' => $designs->map(fn (SiteTemplate $design): array => [
                'slug' => $design->slug,
                'name' => $design->name,
                'card' => CardProps::forThumbnail($design, $sample),
            ])->values(),
        ]);
    }

    public function update(StoreWeddingSiteRequest $request, Wedding $wedding, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $attributes = $request->siteAttributes();
        $site = $wedding->site;

        // Lossless: a guest scans this from the screen, and compression blur breaks the scan.
        if ($request->hasFile('gift_qr_image')) {
            $storeImage->delete($site?->gift_qr_image);
            $attributes['gift_qr_image'] = $storeImage->handle($request->file('gift_qr_image'), 'sites/'.$wedding->id, lossless: true);
        }

        $attributes['slot_images'] = $this->slotImages($request, $wedding, $storeImage);
        // The cover photo is also what the link preview paints, and that is drawn
        // from a column rather than from the design's slots.
        $attributes['cover_image'] = $attributes['slot_images']['cover_image'] ?? null;

        $site = $wedding->site()->updateOrCreate([], $attributes);

        return redirect()
            ->route('site.edit')
            ->with('status', $site->is_published
                ? __('flash.couple.site_updated_published', ['url' => $site->url()])
                : __('flash.couple.site_saved_draft'));
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
            ? __('flash.couple.site_published', ['url' => $site->url()])
            : __('flash.couple.site_unpublished'));
    }

    /**
     * Whether an address is free, asked by the editor as the couple types. It
     * runs the same rules the save does, and offers free alternatives when not.
     */
    public function checkSubdomain(Request $request): JsonResponse
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('update', $wedding);

        $subdomain = mb_strtolower(trim($request->string('subdomain')->toString()));

        $validator = Validator::make(
            ['subdomain' => $subdomain],
            ['subdomain' => StoreWeddingSiteRequest::subdomainRules($wedding->site)],
            StoreWeddingSiteRequest::subdomainMessages(),
            ['subdomain' => 'alamat web'],
        );

        $available = $validator->passes();

        return response()->json([
            'subdomain' => $subdomain,
            'available' => $available,
            'message' => $available ? 'Tersedia! Kad anda akan berada di '.$subdomain.'.'.config('neekah.site_domain') : $validator->errors()->first('subdomain'),
            'suggestions' => $available || $subdomain === '' ? [] : WeddingSite::suggestSubdomains(
                $subdomain,
                $wedding->event_date->year,
                $wedding->site,
            ),
        ]);
    }

    /**
     * A live preview of the couple's own content, without publishing it.
     */
    public function preview(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $site = $wedding->site ?? $this->draftFor($wedding);
        $template = $site->design();

        return view('sites.show', [
            'site' => $site,
            'template' => $template,
            'preview' => true,
            'props' => VueProps::for(CardProps::forSite($site, $template, preview: true)),
        ]);
    }

    /**
     * What the editor's form starts with.
     *
     * @return array<string, mixed>
     */
    private function formValues(WeddingSite $site): array
    {
        $values = [
            'template' => old('template', $site->template),
            'subdomain' => old('subdomain', $site->subdomain),
            'bride_name' => old('bride_name', $site->bride_name),
            'groom_name' => old('groom_name', $site->groom_name),
            'bride_short' => old('bride_short', $site->shortName('bride')),
            'groom_short' => old('groom_short', $site->shortName('groom')),
            'bride_father' => old('bride_father', $site->bride_father),
            'bride_mother' => old('bride_mother', $site->bride_mother),
            'groom_father' => old('groom_father', $site->groom_father),
            'groom_mother' => old('groom_mother', $site->groom_mother),
            'bride_bio' => old('bride_bio', $site->bride_bio),
            'groom_bio' => old('groom_bio', $site->groom_bio),
            'salutation' => old('salutation', $site->salutation),
            'invitation_note' => old('invitation_note', $site->invitation_note),
            'event_date' => old('event_date', $site->event_date?->toDateString()),
            'starts_at' => old('starts_at', $site->starts_at ? Carbon::parse($site->starts_at)->format('H:i') : null),
            'ends_at' => old('ends_at', $site->ends_at ? Carbon::parse($site->ends_at)->format('H:i') : null),
            'venue_name' => old('venue_name', $site->venue_name),
            'venue_address' => old('venue_address', $site->venue_address),
            'map_url' => old('map_url', $site->map_url),
            'rsvp_enabled' => (bool) old('rsvp_enabled', $site->rsvp_enabled),
            'rsvp_deadline' => old('rsvp_deadline', $site->rsvp_deadline?->toDateString()),
            'closing_note' => old('closing_note', $site->closing_note),
            'gift_enabled' => (bool) old('gift_enabled', $site->gift_enabled),
            'gift_note' => old('gift_note', $site->gift_note),
            'wishes_enabled' => (bool) old('wishes_enabled', $site->wishes_enabled ?? true),
            'music_enabled' => (bool) old('music_enabled', $site->music_enabled),
            'music_track_id' => old('music_track_id', $site->music_track_id),
            'palette' => (object) Palettes::sanitize($site->palette ?? []),
            'fonts' => (object) collect($site->fonts ?? [])->only(Fonts::ROLES)->all(),
            'widgets' => $site->widgetKeys(),
            'gift_qr_url' => $site->giftQrUrl(),
            'itinerary' => array_values(old('itinerary', $site->itinerary ?? [])),
            'contacts' => array_values(old('contacts', $site->contacts ?? [])),
            'gift_accounts' => array_values(old('gift_accounts', $site->gift_accounts ?? [])),
        ];

        $values['photos'] = collect(Catalog::photoSlotKeys())
            ->mapWithKeys(fn (string $slot): array => [$slot => $site->slotImage($slot)])
            ->all();

        return $values;
    }

    /**
     * One design, as much of it as the renderer and the picker need.
     *
     * @return array<string, mixed>
     */
    private function designPayload(SiteTemplate $design): array
    {
        return [
            'slug' => $design->slug,
            'name' => $design->name,
            'description' => $design->description,
            'category' => $design->category,
            'style' => $design->style,
            'premium' => $design->is_premium,
            'canvases' => $design->canvases(),
            'palette' => $design->palette(),
            'fonts' => $design->fonts(),
            'photoSlots' => $design->photoSlots(),
        ];
    }

    /**
     * Names and categories only, so the design tab can be browsed before any
     * artwork has been fetched.
     *
     * @return array<string, mixed>
     */
    private function designIndex(): array
    {
        $designs = SiteTemplate::active()->ordered()->get();

        return [
            'categories' => $designs->pluck('category')->unique()->values(),
            'designs' => $designs->map(fn (SiteTemplate $design): array => [
                'slug' => $design->slug,
                'name' => $design->name,
                'category' => $design->category,
                'style' => $design->style,
                'premium' => $design->is_premium,
                'description' => $design->description,
                // Four swatches are enough to tell two designs apart in a list.
                'swatches' => collect($design->palette())->only(['bg', 'head', 'acc', 'card'])->values(),
            ])->values(),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function photoSlotLabels(): array
    {
        return collect(Catalog::photoSlotKeys())
            ->mapWithKeys(fn (string $slot): array => [$slot => __('pages.card_slots.'.$slot)])
            ->all();
    }

    /**
     * The photo in each of the design's slots after this save: what was already
     * there, minus what the couple removed, plus what they just uploaded.
     *
     * @return array<string, string>
     */
    private function slotImages(StoreWeddingSiteRequest $request, Wedding $wedding, StoreOptimizedImage $storeImage): array
    {
        $existing = collect($wedding->site?->slot_images ?? [])->only(Catalog::photoSlotKeys());

        foreach ($request->input('remove_photos', []) as $slot) {
            $storeImage->delete($existing[$slot] ?? null);
            $existing->forget($slot);
        }

        foreach (Catalog::photoSlotKeys() as $slot) {
            $file = $request->file('photos.'.$slot);

            if ($file === null) {
                continue;
            }

            $storeImage->delete($existing[$slot] ?? null);
            $existing[$slot] = $storeImage->handle($file, 'sites/'.$wedding->id);
        }

        return $existing->all();
    }

    /**
     * A draft built from the wedding project so the editor is never empty.
     */
    private function draftFor(Wedding $wedding): WeddingSite
    {
        [$bride, $groom] = array_pad(preg_split('/\s*&\s*/', $wedding->title, 2) ?: [], 2, '');

        // Start from an address nobody holds, so the first save does not bounce.
        $base = Str::slug($wedding->title) ?: 'majlis-'.$wedding->id;
        $subdomain = WeddingSite::suggestSubdomains($base, $wedding->event_date->year, limit: 1)[0] ?? $base.'-'.$wedding->id;

        return new WeddingSite([
            'wedding_id' => $wedding->id,
            'subdomain' => $subdomain,
            'template' => SiteTemplate::active()->ordered()->value('slug') ?? 'royal-songket-gold',
            'bride_name' => $bride ?: 'Pengantin Perempuan',
            'groom_name' => $groom ?: 'Pengantin Lelaki',
            'event_date' => $wedding->event_date,
            'starts_at' => '11:00',
            'ends_at' => '16:00',
            'venue_name' => $wedding->city,
            'venue_address' => $wedding->city.', '.$wedding->state,
            'salutation' => 'Dengan penuh kesyukuran, kami menjemput Dato\' / Datin / Tuan / Puan / Encik / Cik ke majlis perkahwinan anakanda kami',
            'itinerary' => [
                ['time' => '11:00 pagi', 'label' => __('props.couple.ketibaan_tetamu')],
                ['time' => '12:30 tengah hari', 'label' => __('props.couple.ketibaan_pengantin')],
                ['time' => '1:00 petang', 'label' => __('props.couple.makan_beradab')],
                ['time' => '4:00 petang', 'label' => __('props.couple.majlis_bersurai')],
            ],
            'contacts' => [],
            'rsvp_enabled' => true,
            'wishes_enabled' => true,
            'closing_note' => 'Kehadiran dan doa restu daripada tuan/puan amatlah kami hargai.',
        ]);
    }
}
