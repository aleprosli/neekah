<?php

namespace App\Http\Controllers\Customer;

use App\Actions\DeleteCameraMedia;
use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCameraAlbumRequest;
use App\Jobs\BuildCameraExport;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\CameraPurchase;
use App\Models\Wedding;
use App\Support\CameraSettings;
use App\Support\Herepay\CameraPaymentGateway;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Kamera Majlis from the couple's side: what it is and what it costs before
 * they buy, their album once they have, and paying for it.
 */
class CameraController extends Controller
{
    /** Items per page of the couple's album grid. */
    public const PAGE = 48;

    /** The table card designs the designer offers. */
    public const DESIGNS = ['ikut-kad', 'klasik', 'bunga', 'minimalis'];

    public function index(Request $request, CameraSettings $settings, CameraPaymentGateway $gateway): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $album = $wedding->cameraAlbum;
        $active = $album?->isActive() ? $album : null;

        return view('customer.camera.index', [
            'props' => VueProps::for([
                'canCheckout' => $settings->isEnabled() && $gateway->isConfigured(),
                'checkoutUrl' => route('camera.checkout', $wedding),
                'tiers' => collect(CameraTier::cases())->map(fn (CameraTier $tier): array => [
                    'value' => $tier->value,
                    'label' => $tier->label(),
                    'price' => $settings->price($tier),
                    'limits' => $settings->limitsFor($tier)->toArray(),
                    'owned' => $active !== null && $active->tier->rank() >= $tier->rank(),
                    'payable' => $this->amountFor($active, $tier, $settings),
                ])->values(),
                'retentionDays' => $settings->retentionDays(),
                'album' => $active ? [
                    'title' => $active->title,
                    'welcome_message' => $active->welcome_message,
                    'guests_can_view' => $active->guests_can_view,
                    'uploads_open' => $active->uploads_open,
                    'tier' => $active->tier->value,
                    'tier_label' => $active->tier->label(),
                    'url' => $active->url(),
                    'photos' => $active->photos_count,
                    'videos' => $active->videos_count,
                    'max_photos' => $active->limits()->maxPhotos,
                    'bytes' => $active->bytes_used,
                    'expires' => $active->expires_at?->translatedFormat('j F Y'),
                    'restricted' => $active->isRestricted(),
                    'export' => [
                        'building' => Cache::has(BuildCameraExport::buildingKey($active)),
                        'at' => $active->exported_at?->translatedFormat('j M Y, g:i A'),
                        'parts' => collect($active->export_paths ?? [])->keys()->map(fn (int $part): string => route('camera.export.download', [$wedding, $part + 1]))->values()->all(),
                    ],
                    'print' => $this->printData($wedding, $active),
                    'urls' => [
                        'design' => route('camera.design', $wedding),
                        'update' => route('camera.update', $wedding),
                        'rotate' => route('camera.rotate', $wedding),
                        'media' => route('camera.media', $wedding),
                        'bulk' => route('camera.media.bulk', $wedding),
                        'export' => route('camera.export', $wedding),
                    ],
                ] : null,
                'purchases' => $wedding->cameraPurchases()->where('status', SubscriptionStatus::Paid)->latest()->limit(10)->get()
                    ->map(fn (CameraPurchase $purchase): array => [
                        'reference' => $purchase->reference,
                        'tier' => $purchase->tier->label(),
                        'amount' => 'RM'.number_format((float) $purchase->amount, 2),
                        'paid_at' => $purchase->paid_at?->translatedFormat('j M Y'),
                    ])->values(),
            ]),
        ]);
    }

    /**
     * Start a purchase and send the couple to pay. The price is stamped now;
     * an upgrade from Basic costs the difference. An album never goes down a
     * tier, and a tier already owned is not sold twice.
     */
    public function checkout(Request $request, Wedding $wedding, CameraSettings $settings, CameraPaymentGateway $gateway): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($settings->isEnabled() && $gateway->isConfigured(), 404);

        $tier = CameraTier::from($request->validate(['tier' => ['required', Rule::enum(CameraTier::class)]])['tier']);
        $active = $wedding->cameraAlbum?->isActive() ? $wedding->cameraAlbum : null;
        $amount = $this->amountFor($active, $tier, $settings);

        if ($amount === null) {
            throw ValidationException::withMessages(['tier' => __('flash.couple.camera_already_owned')]);
        }

        $purchase = $wedding->cameraPurchases()->create([
            'user_id' => $request->user()->id,
            'reference' => CameraPurchase::generateReference(),
            'tier' => $tier,
            'kind' => $active ? CameraPurchase::KIND_UPGRADE : CameraPurchase::KIND_NEW,
            'amount' => $amount,
            'status' => SubscriptionStatus::Pending,
            'gateway' => CameraPurchase::GATEWAY_HEREPAY,
        ]);

        try {
            $url = $gateway->createPaymentLink($purchase, $request->user());
        } catch (Throwable $exception) {
            report($exception);
            $purchase->update(['status' => SubscriptionStatus::Failed]);

            return back()->withErrors(['tier' => __('flash.couple.camera_checkout_failed')]);
        }

        $purchase->update(['payment_url' => $url]);

        return redirect()->away($url);
    }

    /**
     * Where Herepay sends the couple back. The callback, not this visit,
     * opens the album; this only reports what is known.
     */
    public function done(Request $request): View
    {
        $purchase = CameraPurchase::query()
            ->where('reference', $request->string('ref')->toString())
            ->whereIn('wedding_id', $request->user()->weddings()->pluck('weddings.id'))
            ->firstOrFail();

        return view('customer.camera.done', [
            'purchase' => $purchase,
            'state' => match ($purchase->status) {
                SubscriptionStatus::Paid => 'paid',
                SubscriptionStatus::Failed => 'failed',
                default => 'waiting',
            },
        ]);
    }

    /**
     * What buying $tier costs now: the full price with no album, the
     * difference when upgrading, or null when the album already has it.
     */
    private function amountFor(?CameraAlbum $active, CameraTier $tier, CameraSettings $settings): ?float
    {
        if ($active === null) {
            return $settings->price($tier);
        }

        if ($tier->rank() <= $active->tier->rank()) {
            return null;
        }

        return max(1.0, $settings->price($tier) - $settings->price($active->tier));
    }

    public function update(UpdateCameraAlbumRequest $request, Wedding $wedding): RedirectResponse
    {
        $album = $this->activeAlbum($wedding);
        $values = $request->safe()->only(['title', 'welcome_message']);
        $values['guests_can_view'] = $request->boolean('guests_can_view');
        $values['uploads_open'] = $request->boolean('uploads_open');

        // A new or removed passcode signs every guest out.
        if ($request->boolean('remove_passcode')) {
            $values += ['passcode_hash' => null, 'passcode_version' => $album->passcode_version + 1];
        } elseif ($request->filled('passcode')) {
            $values += ['passcode_hash' => Hash::make($request->string('passcode')->toString()), 'passcode_version' => $album->passcode_version + 1];
        }

        $album->update($values);

        return back()->with('status', __('flash.couple.camera_saved'));
    }

    /** A new guest address: the old QR and link stop working at once. */
    public function rotate(Request $request, Wedding $wedding): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        $this->activeAlbum($wedding)->update(['token' => CameraAlbum::freshToken()]);

        return back()->with('status', __('flash.couple.camera_link_rotated'));
    }

    /**
     * The album for the couple, newest first, a page at a time.
     */
    public function media(Request $request, Wedding $wedding): JsonResponse
    {
        Gate::authorize('view', $wedding);
        $album = $wedding->cameraAlbum ?? abort(404);

        $page = $album->readyMedia()
            ->when(in_array($request->query('type'), ['photo', 'video'], true), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->integer('before') > 0, fn ($query) => $query->where('id', '<', $request->integer('before')))
            ->orderByDesc('id')
            ->limit(self::PAGE + 1)
            ->get();

        $items = $page->take(self::PAGE)->map(fn (CameraMedia $media): array => [
            'id' => $media->id,
            'type' => $media->type->value,
            'url' => $media->url(),
            'thumb' => $media->thumbnailUrl() ?? $media->url(),
            'by' => $media->uploader_name,
            'at' => $media->created_at->translatedFormat('j M, g:i A'),
        ])->values();

        return response()->json(['items' => $items, 'next' => $page->count() > self::PAGE ? $items->last()['id'] : null]);
    }

    /**
     * Delete one or many. The ids are looked up inside this album only, so
     * an id from another wedding is simply not found.
     */
    public function destroyMedia(Request $request, Wedding $wedding, DeleteCameraMedia $delete): JsonResponse
    {
        Gate::authorize('update', $wedding);
        $album = $wedding->cameraAlbum ?? abort(404);
        $ids = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']])['ids'];

        $media = $album->media()->whereKey($ids)->get();
        $media->each(fn (CameraMedia $item) => $delete->handle($item));

        return response()->json(['deleted' => $media->count()]);
    }

    public function export(Request $request, Wedding $wedding): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        $album = $this->activeAlbum($wedding);

        if (Cache::add(BuildCameraExport::buildingKey($album), true, now()->addHour())) {
            BuildCameraExport::dispatch($album);
        }

        return back()->with('status', __('flash.couple.camera_export_queued'));
    }

    public function downloadExport(Request $request, Wedding $wedding, int $part): RedirectResponse
    {
        Gate::authorize('view', $wedding);
        $path = ($wedding->cameraAlbum?->export_paths ?? [])[$part - 1] ?? abort(404);

        return redirect()->away(Storage::disk('public')->url($path));
    }

    /** Remember the couple's table card design and options. */
    public function design(Request $request, Wedding $wedding): JsonResponse
    {
        Gate::authorize('update', $wedding);
        $validated = $request->validate([
            'design' => ['required', Rule::in(self::DESIGNS)],
            'size' => ['required', Rule::in(['a6', 'a5'])],
            'per_sheet' => ['required', 'integer', Rule::in([1, 2, 4])],
            'headline' => ['nullable', 'string', 'max:60'],
        ]);

        $this->activeAlbum($wedding)->update([
            'qr_design' => $validated['design'],
            'qr_options' => collect($validated)->except('design')->all(),
        ]);

        return response()->json(['saved' => true]);
    }

    /**
     * What the table card designer needs: the address the QR encodes, the
     * couple's names and date, their saved choices, and their invitation's
     * colours and type for the "Ikut kad" design when they have a card.
     *
     * @return array<string, mixed>
     */
    private function printData(Wedding $wedding, CameraAlbum $album): array
    {
        $site = $wedding->site()->with('siteTemplate')->first();
        $template = $site?->siteTemplate;

        return [
            'url' => $album->url(),
            'title' => $album->title ?: $wedding->title,
            'date' => $wedding->event_date->translatedFormat('j F Y'),
            'design' => $album->qr_design ?: 'ikut-kad',
            'options' => $album->qr_options ?? (object) [],
            'card' => $template ? [
                'palette' => collect($template->palette($site->palette))->only(['bg', 'head', 'acc', 'ink'])->all(),
                'fonts' => collect($template->fonts($site->fonts))->only(['d', 's'])->all(),
            ] : null,
        ];
    }

    private function activeAlbum(Wedding $wedding): CameraAlbum
    {
        $album = $wedding->cameraAlbum;
        abort_unless($album?->isActive(), 404);

        return $album;
    }
}
