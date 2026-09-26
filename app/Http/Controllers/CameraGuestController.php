<?php

namespace App\Http\Controllers;

use App\Actions\CompleteCameraUpload;
use App\Actions\DeleteCameraMedia;
use App\Actions\PurgeCameraAlbum;
use App\Actions\ReserveCameraUpload;
use App\Enums\CameraMediaStatus;
use App\Enums\CameraMediaType;
use App\Enums\CameraTier;
use App\Enums\CameraWishType;
use App\Jobs\SendTelegramAlert;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Support\Camera\AudioSniffer;
use App\Support\Camera\CameraGuest;
use App\Support\Camera\CameraUploadTarget;
use App\Support\ImageSettings;
use App\Support\Seo;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * The Kamera Majlis page a guest opens from the QR, and everything it posts.
 * No account: the token in the address lets them in, a passcode too when the
 * couple set one, and the phone is known by a hashed cookie.
 *
 * A file never passes through here in production: the phone reserves a
 * place, sends the file straight to storage (CameraUploadTarget), then says
 * it is done. Only local and test storage use the PUT route below.
 */
class CameraGuestController extends Controller
{
    /** Newest-first page size of the gallery. */
    public const PAGE = 30;

    /** How long a guest can take back their own upload. */
    public const OWN_DELETE_HOURS = 24;

    /** The longest written wish. */
    public const WISH_MAX_CHARACTERS = 1000;

    /** The longest voice wish, and the most it may weigh. */
    public const VOICE_MAX_SECONDS = 60;

    public const VOICE_MAX_KILOBYTES = 3072;

    /** Wishes one phone may leave in one album. */
    public const WISHES_PER_DEVICE = 20;

    public function show(Request $request, CameraAlbum $album, Seo $seo): View
    {
        $seo->noindex();
        $album->loadMissing('wedding');
        $guest = new CameraGuest($request);
        $guest->deviceHash();
        $limits = $album->limits();

        return view('camera.show', [
            'album' => $album,
            'closed' => ! $album->isActive(),
            'props' => VueProps::for([
                'album' => [
                    'title' => $album->displayTitle(),
                    'date' => $album->eventDate()->translatedFormat('j F Y'),
                    'welcome' => $album->welcome_message,
                    'tier' => $album->tier->value,
                    'active' => $album->isActive(),
                    'accepts_uploads' => $album->acceptsUploads(),
                    'guests_can_view' => $album->guests_can_view,
                    'limits' => $limits->toArray(),
                    'photos' => $album->photos_count,
                    'allows_voice' => $album->tier === CameraTier::Pro,
                    'wish_max_characters' => self::WISH_MAX_CHARACTERS,
                    'voice_max_seconds' => self::VOICE_MAX_SECONDS,
                ],
                'entered' => $guest->mayEnter($album),
                'name' => $guest->name(),
                'urls' => [
                    'enter' => route('camera.enter', $album),
                    'name' => route('camera.name', $album),
                    'reserve' => route('camera.upload.reserve', $album),
                    'gallery' => route('camera.gallery', $album),
                    'wish' => route('camera.guest.wish', $album),
                ],
            ]),
        ]);
    }

    /** Check the passcode, a few tries at a time per phone and per album. */
    public function enter(Request $request, CameraAlbum $album): RedirectResponse
    {
        $request->validate(['passcode' => ['required', 'string', 'max:32']]);

        $keys = ['camera-pass:'.$album->id.':'.$request->ip() => 5, 'camera-pass:'.$album->id => 50];

        foreach ($keys as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw ValidationException::withMessages(['passcode' => __('validation.custom.camera_passcode_throttled', ['minutes' => (int) ceil(RateLimiter::availableIn($key) / 60)])]);
            }
        }

        if (! $album->isRestricted() || ! Hash::check($request->string('passcode')->toString(), (string) $album->passcode_hash)) {
            foreach ($keys as $key => $max) {
                RateLimiter::hit($key, 600);
            }

            throw ValidationException::withMessages(['passcode' => __('validation.custom.camera_passcode_wrong')]);
        }

        (new CameraGuest($request))->enter($album);

        return redirect()->route('camera.show', $album);
    }

    public function name(Request $request, CameraAlbum $album): JsonResponse
    {
        $this->ensureEntered($request, $album);
        $name = $request->validate(['name' => ['required', 'string', 'max:40']])['name'];

        (new CameraGuest($request))->rememberName($name);

        return response()->json(['name' => mb_substr(trim(strip_tags($name)), 0, 40)]);
    }

    /** Hold a place for a file, and say where to send it. */
    public function reserve(Request $request, CameraAlbum $album, ReserveCameraUpload $reserve): JsonResponse
    {
        $this->ensureEntered($request, $album);

        $validated = $request->validate([
            'type' => ['required', Rule::enum(CameraMediaType::class)],
            'mime' => ['required', 'string', 'max:60'],
            'bytes' => ['required', 'integer', 'min:1'],
            'seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $guest = new CameraGuest($request);
        $media = $reserve->handle(
            $album,
            CameraMediaType::from($validated['type']),
            $validated['mime'],
            (int) $validated['bytes'],
            isset($validated['seconds']) ? (int) $validated['seconds'] : null,
            $guest->deviceHash(),
            $guest->name(),
        );

        return response()->json([
            'id' => $media->id,
            'target' => CameraUploadTarget::for($media->setRelation('album', $album)),
            'complete' => route('camera.upload.complete', [$album, $media]),
        ]);
    }

    /**
     * Local and test storage only: stream the body onto the disk. The address
     * is signed and short-lived, and the body must be the size reserved.
     */
    public function receive(Request $request, CameraAlbum $album, CameraMedia $media): Response
    {
        abort_unless($media->camera_album_id === $album->id && $media->status === CameraMediaStatus::Reserved, 404);
        abort_unless($media->device_hash === (new CameraGuest($request))->deviceHash(), 403);

        $body = $request->getContent();
        abort_unless(strlen($body) === $media->declared_bytes, 422);

        Storage::disk('public')->put((string) $media->incoming_path, $body);

        return response()->noContent();
    }

    public function complete(Request $request, CameraAlbum $album, CameraMedia $media, CompleteCameraUpload $complete): JsonResponse
    {
        $this->ensureEntered($request, $album);
        abort_unless($media->camera_album_id === $album->id, 404);
        abort_unless($media->device_hash === (new CameraGuest($request))->deviceHash(), 403);

        $complete->handle($media);

        return response()->json(['status' => $media->fresh()->status->value]);
    }

    /**
     * The album, newest first: everyone's when the couple lets guests see it,
     * otherwise only this phone's. Asked again with the same ETag while
     * nothing changed, it answers 304 without building the page.
     */
    public function gallery(Request $request, CameraAlbum $album): JsonResponse
    {
        $this->ensureEntered($request, $album);
        $device = (new CameraGuest($request))->deviceHash();

        $response = response()->json()
            ->setEtag($album->contentsTag('guest', $device, (string) (int) $album->guests_can_view, (string) $request->integer('before')))
            ->setPrivate();
        $response->headers->addCacheControlDirective('no-cache');

        if ($response->isNotModified($request)) {
            return $response;
        }

        $page = $album->readyMedia()
            ->when(! $album->guests_can_view, fn ($query) => $query->where('device_hash', $device))
            ->when($request->integer('before') > 0, fn ($query) => $query->where('id', '<', $request->integer('before')))
            ->orderByDesc('id')
            ->limit(self::PAGE + 1)
            ->get();

        $items = $page->take(self::PAGE)->map(fn (CameraMedia $media): array => [
            'id' => $media->id,
            'type' => $media->type->value,
            'url' => $media->url(),
            'display' => $media->displayUrl(),
            'thumb' => $media->thumbnailUrl() ?? $media->url(),
            'by' => $media->uploader_name,
            'delete_url' => $this->mayDelete($media, $device) ? route('camera.media.destroy', [$album, $media]) : null,
            'report_url' => $media->device_hash === $device ? null : route('camera.media.report', [$album, $media]),
        ])->values();

        return $response->setData([
            'items' => $items,
            'next' => $page->count() > self::PAGE ? $items->last()['id'] : null,
            'photos' => $album->photos_count,
        ]);
    }

    public function destroy(Request $request, CameraAlbum $album, CameraMedia $media, DeleteCameraMedia $delete): JsonResponse
    {
        $this->ensureEntered($request, $album);
        abort_unless($media->camera_album_id === $album->id, 404);
        abort_unless($this->mayDelete($media, (new CameraGuest($request))->deviceHash()), 403);

        $delete->handle($media);

        return response()->json(['deleted' => true]);
    }

    /**
     * A guest flags something in the album that should not be there. It
     * waits on the admin page and the admin chat hears about it at once;
     * nothing is hidden until an admin looks, so one guest cannot empty an
     * album by reporting it.
     */
    public function report(Request $request, CameraAlbum $album, CameraMedia $media): JsonResponse
    {
        $this->ensureEntered($request, $album);
        abort_unless($media->camera_album_id === $album->id && $media->status === CameraMediaStatus::Ready, 404);
        $reason = $request->validate(['reason' => ['nullable', 'string', 'max:300']])['reason'] ?? null;

        if ($media->reported_at === null) {
            $media->update(['reported_at' => now(), 'report_reason' => filled($reason) ? trim(strip_tags($reason)) : null]);

            SendTelegramAlert::about('🚩 <b>Neekah Kenangan file reported</b>', [
                'Wedding' => $album->wedding->title,
                'Reason' => $media->report_reason,
                'File' => $media->url(),
                'Review' => route('admin.camera.index'),
            ]);
        }

        return response()->json(['reported' => true]);
    }

    /**
     * A wish for the couple: a written message on every tier, or a short
     * voice recording on Pro. Only the couple sees it. The recording is
     * checked by its first bytes, never by the type the browser claimed.
     */
    public function wish(Request $request, CameraAlbum $album): JsonResponse
    {
        $this->ensureEntered($request, $album);
        abort_unless($album->acceptsUploads(), 410);

        $guest = new CameraGuest($request);
        $device = $guest->deviceHash();

        if ($album->wishes()->where('device_hash', $device)->count() >= self::WISHES_PER_DEVICE) {
            throw ValidationException::withMessages(['message' => __('validation.custom.camera_wish_limit')]);
        }

        if (! $request->hasFile('audio')) {
            $message = $request->validate(['message' => ['required', 'string', 'max:'.self::WISH_MAX_CHARACTERS]])['message'];

            $album->wishes()->create([
                'type' => CameraWishType::Text,
                'message' => trim(strip_tags($message)),
                'guest_name' => $guest->name(),
                'device_hash' => $device,
            ]);

            return response()->json(['saved' => true]);
        }

        abort_unless($album->tier === CameraTier::Pro, 403);
        $validated = $request->validate([
            'audio' => ['required', 'file', 'max:'.self::VOICE_MAX_KILOBYTES],
            'seconds' => ['required', 'integer', 'min:1', 'max:'.self::VOICE_MAX_SECONDS],
        ]);

        $contents = (string) file_get_contents($validated['audio']->getRealPath());
        $format = AudioSniffer::detect(substr($contents, 0, 16));

        if ($format === null) {
            throw ValidationException::withMessages(['audio' => __('validation.custom.camera_voice_unreadable')]);
        }

        $path = PurgeCameraAlbum::directory($album).'/wishes/'.Str::random(40).'.'.$format['extension'];
        Storage::disk('public')->put($path, $contents, ['CacheControl' => CameraAlbum::CACHE_CONTROL]);

        $album->wishes()->create([
            'type' => CameraWishType::Voice,
            'audio_path' => $path,
            'mime' => $format['mime'],
            'bytes' => strlen($contents),
            'duration_seconds' => (int) $validated['seconds'],
            'guest_name' => $guest->name(),
            'device_hash' => $device,
        ]);

        return response()->json(['saved' => true]);
    }

    /**
     * Without JavaScript: an ordinary photo form, within the server's upload
     * limits. It goes through the same reservation and checks.
     */
    public function fallbackUpload(Request $request, CameraAlbum $album, ImageSettings $images, ReserveCameraUpload $reserve, CompleteCameraUpload $complete): RedirectResponse
    {
        $this->ensureEntered($request, $album);
        $request->validate(['photo' => ['required', ...$images->uploadRules()]]);

        $file = $request->file('photo');
        $guest = new CameraGuest($request);
        $media = $reserve->handle($album, CameraMediaType::Photo, (string) $file->getMimeType(), (int) $file->getSize(), null, $guest->deviceHash(), $guest->name());

        Storage::disk('public')->put((string) $media->incoming_path, (string) file_get_contents($file->getRealPath()));
        $complete->handle($media);

        return redirect()->route('camera.show', $album)->with('status', __('pages.camera.fallback_uploaded'));
    }

    private function mayDelete(CameraMedia $media, string $device): bool
    {
        return $media->device_hash === $device && $media->created_at->greaterThan(now()->subHours(self::OWN_DELETE_HOURS));
    }

    private function ensureEntered(Request $request, CameraAlbum $album): void
    {
        abort_unless($album->isActive(), 410);
        abort_unless((new CameraGuest($request))->mayEnter($album), 403);
    }
}
