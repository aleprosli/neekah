<?php

namespace App\Http\Controllers\Customer;

use App\Actions\StoreOptimizedImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingSiteRequest;
use App\Models\SiteTemplate;
use App\Models\Wedding;
use App\Models\WeddingSite;
use App\Support\CardArt;
use App\Support\CardDesign;
use App\Support\CardSections;
use App\Support\ImageSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
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

        $templates = SiteTemplate::active()->ordered()->get();
        $images = app(ImageSettings::class);
        $published = $wedding->site;

        return view('customer.site', [
            'wedding' => $wedding,
            'props' => VueProps::for([
                'exists' => $site->exists,
                'action' => route('weddings.site.update', $wedding),
                'domain' => config('neekah.site_domain'),
                'subdomainCheckUrl' => route('site.subdomain'),
                'imageHint' => $images->uploadHint('1600 × 1200px atau lebih'),
                'limits' => [
                    'templates' => $templates->count(),
                    'gallery_url' => route('sites.templates'),
                    'preview_url' => route('site.preview'),
                    'draft_preview_url' => route('site.preview.draft'),
                    // The same ceilings StoreWeddingSiteRequest enforces, so the
                    // form cannot offer a row the server will reject.
                    'itinerary' => 12,
                    'contacts' => 6,
                    'gift_accounts' => 4,
                ],
                'site' => [
                    'template' => old('template', $site->template),
                    'subdomain' => old('subdomain', $site->subdomain),
                    'bride_name' => old('bride_name', $site->bride_name),
                    'groom_name' => old('groom_name', $site->groom_name),
                    'bride_parents' => old('bride_parents', $site->bride_parents),
                    'groom_parents' => old('groom_parents', $site->groom_parents),
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
                    'cover_url' => $site->cover_image ? Storage::disk('public')->url($site->cover_image) : null,
                    'gift_qr_url' => $site->giftQrUrl(),
                    'itinerary' => array_values(old('itinerary', $site->itinerary ?? [])),
                    'contacts' => array_values(old('contacts', $site->contacts ?? [])),
                    'gift_accounts' => array_values(old('gift_accounts', $site->gift_accounts ?? [])),
                ],
                // What the card is set to right now — the template's own
                // choices unless the couple has changed something — plus the
                // arrangement, and the catalogue each picker draws from.
                'design' => $this->designProps($site),
                'sections' => CardSections::forEditor(old('sections', $site->sections)),
                'templateGroups' => $templates->groupBy('style')
                    ->map(fn ($group, string $style): array => [
                        'style' => $style,
                        'templates' => $group->map(fn (SiteTemplate $template): array => [
                            'slug' => $template->slug,
                            'name' => $template->name,
                            'url' => route('sites.templates.show', $template),
                            // The thumbnail is a Blade partial shared with the
                            // public gallery; rendering it here keeps one drawing.
                            'thumbnail' => view('sites.partials.thumbnail', ['template' => $template->toCardDesign()])->render(),
                        ])->values(),
                    ])->values(),
                'status' => $site->exists ? [
                    'published' => (bool) $site->is_published,
                    'url' => $site->url(),
                    'views' => number_format($site->views),
                    'rsvp_count' => $published?->confirmedPax() ?? 0,
                    'guests_url' => route('guests.index'),
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
     * The resolved design a couple is editing, beside the lists they may pick
     * from. Values are the resolved ones, so a couple who has changed nothing
     * still sees what their template chose rather than an empty form.
     *
     * @return array<string, mixed>
     */
    private function designProps(WeddingSite $site): array
    {
        $design = $site->design();

        return [
            'current' => [
                'layout' => $design->layout(),
                'ornament' => $design->ornament(),
                'motion' => $design->motion(),
                'artwork' => $design->artwork() ?? 'none',
                'texture' => $design->texture() ?? 'none',
                'eyebrow' => $design->eyebrow(),
                'bismillah' => $design->showsBismillah(),
                'palette' => collect(CardDesign::PALETTE_KEYS)
                    ->mapWithKeys(fn (string $key): array => [$key => $design->palette()[$key] ?? '#ffffff'])
                    ->all(),
                'type' => [
                    'script' => $design->toArray()['type']['script'] ?? null,
                    'body' => $design->toArray()['type']['body'] ?? null,
                ],
            ],
            'options' => [
                'layout' => collect(SiteTemplate::LAYOUTS)->map(fn (string $v): array => ['value' => $v, 'label' => ucfirst($v)])->all(),
                'ornament' => collect(SiteTemplate::ORNAMENTS)->map(fn (string $v): array => ['value' => $v, 'label' => ucfirst($v)])->all(),
                'motion' => CardArt::options(CardArt::MOTION_LABELS),
                'artwork' => CardArt::options(CardArt::ARTWORK),
                'texture' => CardArt::options(CardArt::TEXTURES),
                'script' => CardArt::fontOptions('script'),
                'body' => CardArt::fontOptions('body'),
            ],
            // --nk-texture for each stock, so the frame can repaint without a redraw.
            'textureCss' => CardArt::textureCssMap(),
            'paletteLabels' => [
                'page' => 'Kertas',
                'ink' => 'Dakwat',
                'name' => 'Nama',
                'accent' => 'Aksen',
                'body' => 'Teks',
                'muted' => 'Teks pudar',
                'panel' => 'Panel',
                'line' => 'Garisan',
                'buttonBg' => 'Butang',
                'buttonText' => 'Teks butang',
            ],
        ];
    }

    public function update(StoreWeddingSiteRequest $request, Wedding $wedding, StoreOptimizedImage $storeImage): RedirectResponse
    {
        $attributes = $request->siteAttributes();

        if ($request->hasFile('cover_image')) {
            $storeImage->delete($wedding->site?->cover_image);
            $attributes['cover_image'] = $storeImage->handle($request->file('cover_image'), 'sites/'.$wedding->id);
        }

        // Lossless: a guest scans this from the screen, and compression blur breaks the scan.
        if ($request->hasFile('gift_qr_image')) {
            $storeImage->delete($wedding->site?->gift_qr_image);
            $attributes['gift_qr_image'] = $storeImage->handle($request->file('gift_qr_image'), 'sites/'.$wedding->id, lossless: true);
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
    /**
     * The card as it would look right now, from a form that has not been saved.
     *
     * The preview is rendered by the same Blade the published card is, so what
     * a couple is looking at cannot drift from what their guests will get — a
     * second copy of the card in JavaScript would.
     *
     * Nothing here is persisted. Validation is deliberately loose: a couple
     * halfway through typing a date should see their card, not an error. The
     * one thing still held to the catalogue is the design, because those
     * values are printed into a style attribute.
     */
    public function previewDraft(Request $request): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $site = $wedding->site ?? $this->draftFor($wedding);

        $site->fill(collect($request->only([
            'bride_name', 'groom_name', 'bride_parents', 'groom_parents', 'salutation',
            'invitation_note', 'venue_name', 'venue_address', 'map_url', 'closing_note',
            'gift_note',
        ]))->filter(fn (mixed $value): bool => is_string($value))->all());

        foreach (['event_date' => 'date', 'starts_at' => 'time', 'ends_at' => 'time', 'rsvp_deadline' => 'date'] as $field => $kind) {
            if ($request->filled($field)) {
                $site->{$field} = $request->input($field);
            }
        }

        foreach (['rsvp_enabled', 'gift_enabled', 'wishes_enabled'] as $flag) {
            $site->{$flag} = $request->boolean($flag);
        }

        $site->itinerary = $this->previewRows($request, 'itinerary', ['time', 'label']);
        $site->contacts = $this->previewRows($request, 'contacts', ['name', 'phone']);
        $site->gift_accounts = $this->previewRows($request, 'gift_accounts', ['bank', 'number']);

        if (SiteTemplate::active()->where('slug', $request->input('template'))->exists()) {
            $site->template = $request->input('template');
            // design() reads the relation, which still holds the old row.
            $site->unsetRelation('siteTemplate');
        }

        $site->design_overrides = CardDesign::clean((array) $request->input('design_overrides', [])) ?: null;
        $site->sections = $request->filled('sections')
            ? CardSections::sanitise((array) $request->input('sections'))
            : null;

        // A picture chosen but not yet saved never reaches the server — the
        // editor keeps it on the couple's own machine and drops it into the
        // frame. All the card has to do is leave the <img> there to be filled,
        // so the layout does not jump when the picture lands.
        foreach (['cover_image' => 'draft_cover', 'gift_qr_image' => 'draft_gift_qr'] as $column => $flag) {
            if ($request->boolean($flag)) {
                $site->{$column} = WeddingSite::DRAFT_IMAGE;
            }
        }

        return view('sites.show', [
            'site' => $site,
            'template' => $site->design(),
            'preview' => true,
            'draft' => true,
        ]);
    }

    /**
     * A repeater's rows, keeping only those with every column filled in, so a
     * half-typed row does not print as a blank line on the card.
     *
     * @param  array<int, string>  $columns
     * @return array<int, array<string, string>>
     */
    private function previewRows(Request $request, string $field, array $columns): array
    {
        return collect($request->input($field, []))
            ->filter(fn (mixed $row): bool => is_array($row) && collect($columns)->every(fn (string $c): bool => filled($row[$c] ?? null)))
            ->map(fn (array $row): array => collect($row)->only(array_merge($columns, ['holder']))->map(fn ($v): string => (string) $v)->all())
            ->values()
            ->all();
    }

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

        // Start from an address nobody holds, so the first save does not bounce.
        $base = Str::slug($wedding->title) ?: 'majlis-'.$wedding->id;
        $subdomain = WeddingSite::suggestSubdomains($base, $wedding->event_date->year, limit: 1)[0] ?? $base.'-'.$wedding->id;

        return new WeddingSite([
            'wedding_id' => $wedding->id,
            'subdomain' => $subdomain,
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
