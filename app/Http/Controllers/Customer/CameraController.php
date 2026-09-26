<?php

namespace App\Http\Controllers\Customer;

use App\Actions\ActivateCameraAlbum;
use App\Actions\DeleteCameraMedia;
use App\Actions\DeleteCameraWish;
use App\Actions\StartPayment;
use App\Enums\CameraMediaType;
use App\Enums\CameraTier;
use App\Enums\PaymentPurpose;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCameraAlbumRequest;
use App\Jobs\BuildCameraExport;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\CameraWish;
use App\Models\Payment;
use App\Models\Wedding;
use App\Support\CameraSettings;
use App\Support\Herepay\HerepayGateway;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Neekah Kenangan from the couple's side: every album they bought (one per
 * majlis) and buying another, each album's own page (gallery, wishes, QR
 * cards, settings), and paying for it.
 */
class CameraController extends Controller
{
    /** Items per page of the couple's album grid. */
    public const PAGE = 48;

    /** Wishes per page. */
    public const WISH_PAGE = 30;

    /** The table card designs the designer offers. */
    public const DESIGNS = ['ikut-kad', 'klasik', 'bunga', 'minimalis'];

    /**
     * A ZIP of chosen files is built while the couple waits, so it is kept
     * small; the whole album goes through the queue instead.
     */
    public const SELECTED_MAX_ITEMS = 100;

    public const SELECTED_MAX_BYTES = 300 * 1024 * 1024;

    public function index(Request $request, CameraSettings $settings, HerepayGateway $gateway): View
    {
        $wedding = $request->user()->weddings()->latest('event_date')->firstOrFail();
        Gate::authorize('view', $wedding);

        $albums = $wedding->cameraAlbums()->with('wedding')->whereNotNull('activated_at')->latest('id')->get();

        return view('customer.camera.index', [
            'props' => VueProps::for([
                'wedding' => ['title' => $wedding->title, 'date' => $wedding->event_date->toDateString()],
                'albums' => $albums->map(fn (CameraAlbum $album): array => $this->summary($album))->values(),
                'buy' => $this->buyProps($wedding, $settings, $gateway),
                'purchases' => $this->receipts($wedding),
                'old' => ['title' => old('title'), 'event_date' => old('event_date')],
            ]),
        ]);
    }

    public function show(CameraAlbum $album, CameraSettings $settings, HerepayGateway $gateway): View
    {
        Gate::authorize('view', $album->wedding);
        abort_if($album->activated_at === null, 404);

        $active = $album->isActive();
        $wedding = $album->wedding;

        return view('customer.camera.show', [
            'album' => $album,
            'props' => VueProps::for([
                'album' => [
                    ...$this->summary($album),
                    'welcome_message' => $album->welcome_message,
                    'raw_title' => $album->title,
                    'event_date' => $album->event_date?->toDateString(),
                    'guests_can_view' => $album->guests_can_view,
                    'uploads_open' => $album->uploads_open,
                    'restricted' => $album->isRestricted(),
                    'bytes' => $album->bytes_used,
                    'export' => [
                        'building' => Cache::has(BuildCameraExport::buildingKey($album)),
                        'at' => $album->exported_at?->translatedFormat('j M Y, g:i A'),
                        'parts' => collect($album->export_paths ?? [])->keys()->map(fn (int $part): string => route('camera.export.download', [$album, $part + 1]))->values()->all(),
                    ],
                    'print' => $active ? $this->printData($wedding, $album) : null,
                    'urls' => [
                        'index' => route('camera.index'),
                        'design' => route('camera.design', $album),
                        'update' => route('camera.update', $album),
                        'rotate' => route('camera.rotate', $album),
                        'media' => route('camera.media', $album),
                        'bulk' => route('camera.media.bulk', $album),
                        'export' => route('camera.export', $album),
                        'export_selected' => route('camera.export.selected', $album),
                        'wishes' => route('camera.wishes', $album),
                    ],
                ],
                'upgrade' => $active ? $this->upgradeProps($album, $settings, $gateway) : null,
                'limits' => ['selected_items' => self::SELECTED_MAX_ITEMS],
            ]),
        ]);
    }

    /**
     * Start a purchase and send the couple to pay: a new album for another
     * majlis, or Pro for one of their Basic albums. The price is stamped now;
     * an upgrade costs the difference, and a tier an album already has is
     * not sold twice.
     */
    public function checkout(Request $request, Wedding $wedding, CameraSettings $settings, HerepayGateway $gateway, StartPayment $start): RedirectResponse
    {
        Gate::authorize('update', $wedding);
        abort_unless($settings->isEnabled() && $gateway->isConfigured(), 404);

        $validated = $request->validate([
            'tier' => ['required', Rule::enum(CameraTier::class)],
            'album' => ['nullable', 'integer', Rule::exists(CameraAlbum::class, 'id')->where('wedding_id', $wedding->id)],
            'title' => ['nullable', 'string', 'max:120'],
            'event_date' => ['nullable', 'date', 'after_or_equal:'.now()->subYear()->toDateString()],
        ], attributes: [
            'title' => __('fields.camera_album_title'),
            'event_date' => __('fields.camera_event_date'),
        ]);

        $tier = CameraTier::from($validated['tier']);
        $album = isset($validated['album']) ? $wedding->cameraAlbums()->find($validated['album']) : null;

        if ($album && ! $album->isActive()) {
            throw ValidationException::withMessages(['tier' => __('flash.couple.camera_album_closed')]);
        }

        $amount = $this->amountFor($album, $tier, $settings);

        if ($amount === null) {
            throw ValidationException::withMessages(['tier' => __('flash.couple.camera_already_owned')]);
        }

        $payment = Payment::query()->create([
            'purpose' => PaymentPurpose::Kenangan,
            'wedding_id' => $wedding->id,
            'camera_album_id' => $album?->id,
            'recorded_by' => $request->user()->id,
            'amount' => $amount,
            'status' => PaymentStatus::Pending,
            'gateway' => $gateway->name(),
            'details' => array_filter([
                'tier' => $tier->value,
                'kind' => $album ? 'upgrade' : 'new',
                'album_title' => $album ? null : (trim(strip_tags((string) ($validated['title'] ?? ''))) ?: null),
                'album_event_date' => $album ? null : ($validated['event_date'] ?? null),
            ], fn (?string $value): bool => $value !== null),
        ]);

        try {
            return redirect()->away($start->handle($payment, $request->user()));
        } catch (RuntimeException $exception) {
            report($exception);

            return back()->withInput()->withErrors(['tier' => __('flash.couple.camera_checkout_failed')]);
        }
    }

    /**
     * Where the couple lands after paying. The gateway's callback, or its
     * signed return, opens the album; this only reports it.
     */
    public function done(Request $request): View
    {
        $payment = Payment::query()
            ->for(PaymentPurpose::Kenangan)
            ->with('album')
            ->where('reference', $request->string('ref')->toString())
            ->whereIn('wedding_id', $request->user()->weddings()->pluck('weddings.id'))
            ->firstOrFail();

        return view('customer.camera.done', [
            'payment' => $payment,
            'tier' => CameraTier::from((string) $payment->detail('tier'))->label(),
            'openUrl' => $payment->album ? route('camera.album', $payment->album) : route('camera.index'),
            'state' => match ($payment->status) {
                PaymentStatus::Paid => 'paid',
                PaymentStatus::Failed, PaymentStatus::Expired => 'failed',
                default => 'waiting',
            },
        ]);
    }

    public function update(UpdateCameraAlbumRequest $request, CameraAlbum $album): RedirectResponse
    {
        $this->ensureActive($album);
        $values = $request->safe()->only(['title', 'welcome_message']);
        $values['guests_can_view'] = $request->boolean('guests_can_view');
        $values['uploads_open'] = $request->boolean('uploads_open');

        // Another date moves when the album is deleted, which counts from it.
        if ($request->has('event_date')) {
            $values['event_date'] = $request->date('event_date');
            $values['expires_at'] = ActivateCameraAlbum::expiryFor($values['event_date'] ?? $album->wedding->event_date);
        }

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
    public function rotate(CameraAlbum $album): RedirectResponse
    {
        Gate::authorize('update', $album->wedding);
        $this->ensureActive($album);
        $album->update(['token' => CameraAlbum::freshToken()]);

        return back()->with('status', __('flash.couple.camera_link_rotated'));
    }

    /**
     * The album for the couple, newest first, a page at a time. Asked again
     * with the same ETag while nothing changed, it answers 304 without
     * building the page.
     */
    public function media(Request $request, CameraAlbum $album): JsonResponse
    {
        Gate::authorize('view', $album->wedding);

        $type = in_array($request->query('type'), ['photo', 'video'], true) ? $request->query('type') : '';
        $before = max(0, $request->integer('before'));
        $response = response()->json()->setEtag($album->contentsTag('couple', $type, (string) $before))->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        if ($response->isNotModified($request)) {
            return $response;
        }

        $page = $album->readyMedia()
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->when($before > 0, fn ($query) => $query->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit(self::PAGE + 1)
            ->get();

        $items = $page->take(self::PAGE)->map(fn (CameraMedia $media): array => [
            'id' => $media->id,
            'type' => $media->type->value,
            'url' => $media->url(),
            'display' => $media->displayUrl(),
            'thumb' => $media->thumbnailUrl() ?? $media->url(),
            'width' => $media->width,
            'height' => $media->height,
            'bytes' => $media->bytes,
            'by' => $media->uploader_name,
            'at' => $media->created_at->translatedFormat('j M, g:i A'),
        ])->values();

        return $response->setData(['items' => $items, 'next' => $page->count() > self::PAGE ? $items->last()['id'] : null]);
    }

    /**
     * Delete one or many. The ids are looked up inside this album only, so
     * an id from another album is simply not found.
     */
    public function destroyMedia(Request $request, CameraAlbum $album, DeleteCameraMedia $delete): JsonResponse
    {
        Gate::authorize('update', $album->wedding);
        $ids = $request->validate(['ids' => ['required', 'array', 'max:100'], 'ids.*' => ['integer']])['ids'];

        $media = $album->media()->whereKey($ids)->get();
        $media->each(fn (CameraMedia $item) => $delete->handle($item));

        return response()->json(['deleted' => $media->count()]);
    }

    /** The whole album, wishes included, built in the queue as ZIP parts. */
    public function export(CameraAlbum $album): RedirectResponse
    {
        Gate::authorize('update', $album->wedding);
        $this->ensureActive($album);

        if (Cache::add(BuildCameraExport::buildingKey($album), true, now()->addHour())) {
            BuildCameraExport::dispatch($album);
        }

        return back()->with('status', __('flash.couple.camera_export_queued'));
    }

    /**
     * The files the couple ticked, as one ZIP straight away. Kept to what can
     * be built while they wait; anything bigger is the whole-album export.
     */
    public function exportSelected(Request $request, CameraAlbum $album): BinaryFileResponse
    {
        Gate::authorize('view', $album->wedding);
        $ids = $request->validate([
            'ids' => ['required', 'array', 'max:'.self::SELECTED_MAX_ITEMS],
            'ids.*' => ['integer'],
        ])['ids'];

        $media = $album->readyMedia()->whereKey($ids)->orderBy('id')->get();
        abort_if($media->isEmpty(), 404);

        if ($media->sum('bytes') > self::SELECTED_MAX_BYTES) {
            throw ValidationException::withMessages(['ids' => __('flash.couple.camera_selection_too_big', ['mb' => self::SELECTED_MAX_BYTES / 1024 / 1024])]);
        }

        $disk = Storage::disk('public');
        $local = storage_path('app/private/camera-exports/selected-'.Str::random(16));
        @mkdir($local, 0775, true);
        $zipPath = $local.'.zip';
        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $copies = [];

        try {
            foreach ($media as $item) {
                if (! $item->path || ! $disk->exists($item->path)) {
                    continue;
                }

                $copy = $local.'/'.$item->id;
                file_put_contents($copy, $disk->readStream($item->path));
                $name = BuildCameraExport::entryName($item);
                $zip->addFile($copy, $name);
                $zip->setCompressionName($name, ZipArchive::CM_STORE);
                $copies[] = $copy;
            }

            $zip->close();
        } finally {
            foreach ($copies as $copy) {
                @unlink($copy);
            }
            @rmdir($local);
        }

        return response()
            ->download($zipPath, Str::slug($album->displayTitle()).'-'.$media->count().'.zip', ['Content-Type' => 'application/zip'])
            ->deleteFileAfterSend();
    }

    public function downloadExport(CameraAlbum $album, int $part): RedirectResponse
    {
        Gate::authorize('view', $album->wedding);
        $path = ($album->export_paths ?? [])[$part - 1] ?? abort(404);

        return redirect()->away(Storage::disk('public')->url($path));
    }

    /** Remember the couple's table card design and options. */
    public function design(Request $request, CameraAlbum $album): JsonResponse
    {
        Gate::authorize('update', $album->wedding);
        $this->ensureActive($album);
        $validated = $request->validate([
            'design' => ['required', Rule::in(self::DESIGNS)],
            'size' => ['required', Rule::in(['a6', 'a5'])],
            'per_sheet' => ['required', 'integer', Rule::in([1, 2, 4])],
            'headline' => ['nullable', 'string', 'max:60'],
        ]);

        $album->update([
            'qr_design' => $validated['design'],
            'qr_options' => collect($validated)->except('design')->all(),
        ]);

        return response()->json(['saved' => true]);
    }

    /** The wishes guests left, newest first, a page at a time. */
    public function wishes(Request $request, CameraAlbum $album): JsonResponse
    {
        Gate::authorize('view', $album->wedding);

        $page = $album->wishes()
            ->when(in_array($request->query('type'), ['text', 'voice'], true), fn ($query) => $query->where('type', $request->query('type')))
            ->when($request->integer('before') > 0, fn ($query) => $query->where('id', '<', $request->integer('before')))
            ->orderByDesc('id')
            ->limit(self::WISH_PAGE + 1)
            ->get();

        $items = $page->take(self::WISH_PAGE)->map(fn (CameraWish $wish): array => [
            'id' => $wish->id,
            'type' => $wish->type->value,
            'message' => $wish->message,
            'audio' => $wish->audioUrl(),
            'seconds' => $wish->duration_seconds,
            'by' => $wish->guest_name,
            'at' => $wish->created_at->translatedFormat('j M, g:i A'),
            'delete_url' => route('camera.wishes.destroy', [$album, $wish]),
        ])->values();

        return response()->json([
            'items' => $items,
            'next' => $page->count() > self::WISH_PAGE ? $items->last()['id'] : null,
        ]);
    }

    public function destroyWish(CameraAlbum $album, CameraWish $wish, DeleteCameraWish $delete): JsonResponse
    {
        Gate::authorize('update', $album->wedding);
        abort_unless($wish->camera_album_id === $album->id, 404);

        $delete->handle($wish);

        return response()->json(['deleted' => true]);
    }

    /**
     * What an album card on the list and the header of its own page show.
     *
     * @return array<string, mixed>
     */
    private function summary(CameraAlbum $album): array
    {
        $covers = $album->isActive()
            ? $album->readyMedia()->where('type', CameraMediaType::Photo)->latest('id')->limit(4)->get()->map(fn (CameraMedia $media): ?string => $media->thumbnailUrl())->filter()->values()->all()
            : [];

        return [
            'id' => $album->id,
            'title' => $album->displayTitle(),
            'date' => $album->eventDate()->translatedFormat('j F Y'),
            'tier' => $album->tier->value,
            'tier_label' => $album->tier->label(),
            'active' => $album->isActive(),
            'purged' => $album->purged_at !== null,
            'expires' => $album->expires_at?->translatedFormat('j F Y'),
            'photos' => $album->photos_count,
            'videos' => $album->videos_count,
            'wishes' => $album->wishes()->count(),
            'max_photos' => $album->limits()->maxPhotos,
            'allows_voice' => $album->tier === CameraTier::Pro,
            'url' => $album->url(),
            'covers' => $covers,
            'show_url' => route('camera.album', $album),
        ];
    }

    /**
     * The tiers to buy a new album with, for another majlis.
     *
     * @return array<string, mixed>
     */
    private function buyProps(Wedding $wedding, CameraSettings $settings, HerepayGateway $gateway): array
    {
        return [
            'can_checkout' => $settings->isEnabled() && $gateway->isConfigured(),
            'checkout_url' => route('camera.checkout', $wedding),
            'retention_days' => $settings->retentionDays(),
            'tiers' => collect(CameraTier::cases())->map(fn (CameraTier $tier): array => [
                'value' => $tier->value,
                'label' => $tier->label(),
                'price' => $settings->price($tier),
                'limits' => $settings->limitsFor($tier)->toArray(),
                'voice' => $tier === CameraTier::Pro,
            ])->values(),
        ];
    }

    /**
     * Moving one Basic album up to Pro, for the difference.
     *
     * @return array<string, mixed>|null
     */
    private function upgradeProps(CameraAlbum $album, CameraSettings $settings, HerepayGateway $gateway): ?array
    {
        $tier = collect(CameraTier::cases())->first(fn (CameraTier $tier): bool => $tier->rank() > $album->tier->rank());

        if (! $tier) {
            return null;
        }

        return [
            'can_checkout' => $settings->isEnabled() && $gateway->isConfigured(),
            'checkout_url' => route('camera.checkout', $album->wedding),
            'tier' => $tier->value,
            'label' => $tier->label(),
            'amount' => $this->amountFor($album, $tier, $settings),
            'limits' => $settings->limitsFor($tier)->toArray(),
        ];
    }

    /**
     * @return list<array<string, string|null>>
     */
    private function receipts(Wedding $wedding): array
    {
        return $wedding->kenanganPayments()->with('album.wedding')->where('status', PaymentStatus::Paid)->limit(20)->get()
            ->map(fn (Payment $payment): array => [
                'reference' => $payment->reference,
                'album' => $payment->album?->displayTitle(),
                'tier' => CameraTier::tryFrom((string) $payment->detail('tier'))?->label(),
                'kind' => $payment->detail('kind'),
                'amount' => 'RM'.number_format((float) $payment->amount, 2),
                'paid_at' => $payment->paid_at?->translatedFormat('j M Y'),
            ])->values()->all();
    }

    /**
     * What buying $tier costs now: the full price for a new album, the
     * difference when upgrading, or null when the album already has it.
     */
    private function amountFor(?CameraAlbum $album, CameraTier $tier, CameraSettings $settings): ?float
    {
        if ($album === null) {
            return $settings->price($tier);
        }

        if ($tier->rank() <= $album->tier->rank()) {
            return null;
        }

        return max(1.0, $settings->price($tier) - $settings->price($album->tier));
    }

    /**
     * What the table card designer needs: the address the QR encodes, the
     * album's title and date, the saved choices, and the invitation's colours
     * and type for the "Ikut kad" design when the couple has a card.
     *
     * @return array<string, mixed>
     */
    private function printData(Wedding $wedding, CameraAlbum $album): array
    {
        $site = $wedding->site()->with('siteTemplate')->first();
        $template = $site?->siteTemplate;

        return [
            'url' => $album->url(),
            'title' => $album->displayTitle(),
            'date' => $album->eventDate()->translatedFormat('j F Y'),
            'design' => $album->qr_design ?: 'ikut-kad',
            'options' => $album->qr_options ?? (object) [],
            'card' => $template ? [
                'palette' => collect($template->palette($site->palette))->only(['bg', 'head', 'acc', 'ink'])->all(),
                'fonts' => collect($template->fonts($site->fonts))->only(['d', 's'])->all(),
            ] : null,
        ];
    }

    private function ensureActive(CameraAlbum $album): void
    {
        abort_unless($album->isActive(), 404);
    }
}
