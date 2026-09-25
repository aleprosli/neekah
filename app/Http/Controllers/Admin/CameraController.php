<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ActivateCameraAlbum;
use App\Actions\DeleteCameraMedia;
use App\Actions\PurgeCameraAlbum;
use App\Enums\CameraAlbumFilter;
use App\Enums\CameraTier;
use App\Enums\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Models\CameraAlbum;
use App\Models\CameraMedia;
use App\Models\CameraPurchase;
use App\Models\User;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Number;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Kamera Majlis for the admin: every album with what it holds and until
 * when, purchases paid outside Herepay, what guests reported, and taking an
 * album down before its time.
 */
class CameraController extends Controller
{
    /** Reported photos shown at once; the oldest report first. */
    public const REPORTED = 30;

    /**
     * Columns for components/ui/DataTable.vue.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'wedding', 'label' => __('pages.admin_camera.col_wedding'), 'type' => 'html'],
            ['key' => 'tier', 'label' => __('pages.admin_camera.col_tier')],
            ['key' => 'media', 'label' => __('pages.admin_camera.col_media'), 'sort' => 'photos_count', 'sortable' => true, 'align' => 'right'],
            ['key' => 'storage', 'label' => __('pages.admin_camera.col_storage'), 'sort' => 'bytes_used', 'sortable' => true, 'align' => 'right'],
            ['key' => 'paid', 'label' => __('pages.admin_camera.col_paid'), 'align' => 'right'],
            ['key' => 'expires', 'label' => __('pages.admin_camera.col_expires'), 'sort' => 'expires_at', 'sortable' => true],
            ['key' => 'state', 'label' => __('pages.admin_camera.col_state'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $filter = CameraAlbumFilter::tryFrom($request->string('filter')->toString());
        $active = CameraAlbumFilter::Active->apply(CameraAlbum::query());

        return view('admin.camera', [
            'props' => VueProps::for([
                'stats' => [
                    ['label' => __('pages.admin_camera.stat_active'), 'value' => (string) $active->clone()->count()],
                    ['label' => __('pages.admin_camera.stat_media'), 'value' => number_format((int) $active->clone()->sum('photos_count') + (int) $active->clone()->sum('videos_count'))],
                    ['label' => __('pages.admin_camera.stat_storage'), 'value' => Number::fileSize((int) $active->clone()->sum('bytes_used'), 1)],
                    ['label' => __('pages.admin_camera.stat_revenue'), 'value' => 'RM'.number_format((float) CameraPurchase::where('status', SubscriptionStatus::Paid)->sum('amount'), 2)],
                ],
                'table' => [
                    'dataUrl' => route('admin.camera.data'),
                    'columns' => self::columns(),
                    'filters' => [TableFilter::fromEnum('filter', CameraAlbumFilter::cases(), $filter?->value)],
                ],
                'reported' => $this->reported(),
                'tiers' => array_map(fn (CameraTier $tier): array => ['value' => $tier->value, 'label' => $tier->label()], CameraTier::cases()),
                'grantUrl' => route('admin.camera.store'),
            ]),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filter = CameraAlbumFilter::tryFrom($request->string('filter')->toString());
        $sort = in_array($request->string('sort')->toString(), ['photos_count', 'bytes_used', 'expires_at'], true)
            ? $request->string('sort')->toString()
            : 'created_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $matching = CameraAlbum::query()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('token', $keyword)
                    ->orWhere('title', 'like', $like)
                    ->orWhereHas('wedding', fn ($wedding) => $wedding->where('title', 'like', $like)
                        ->orWhereHas('user', fn ($user) => $user->where('email', 'like', $like)->orWhere('name', 'like', $like))));
            });

        $albums = $matching->clone()
            ->with(['wedding.user'])
            ->withSum(['purchases as paid_total' => fn ($query) => $query->where('status', SubscriptionStatus::Paid)], 'amount')
            ->when($filter, fn ($query) => $filter->apply($query))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $albums->getCollection()->map(fn (CameraAlbum $album): array => [
                'id' => $album->id,
                'wedding' => view('components.admin.camera-wedding-cell', ['album' => $album])->render(),
                'tier' => $album->tier->label(),
                'media' => __('pages.admin_camera.media_count', ['photos' => $album->photos_count, 'videos' => $album->videos_count]),
                'storage' => Number::fileSize($album->bytes_used, 1),
                'paid' => 'RM'.number_format((float) $album->paid_total, 2),
                'expires' => $album->expires_at?->translatedFormat('j M Y') ?? '—',
                'state' => view('components.admin.status-pill', $this->stateOf($album))->render(),
                'actions' => $album->purged_at ? [] : [$this->purgeAction($album)],
            ])->all(),
            'filters' => ['filter' => collect(CameraAlbumFilter::cases())
                ->mapWithKeys(fn (CameraAlbumFilter $case): array => [$case->value => $case->apply($matching->clone())->count()])
                ->prepend($matching->clone()->count(), '')
                ->all()],
            'meta' => [
                'total' => $albums->total(),
                'per_page' => $albums->perPage(),
                'current_page' => $albums->currentPage(),
                'last_page' => $albums->lastPage(),
            ],
        ]);
    }

    /**
     * A purchase paid outside Herepay (a bank transfer, a gift): the couple
     * is found by the email of either partner.
     */
    public function store(Request $request, ActivateCameraAlbum $activate): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'tier' => ['required', Rule::enum(CameraTier::class)],
            'amount' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $wedding = User::query()->where('email', $validated['email'])->first()
            ?->weddings()->latest('weddings.created_at')->first();

        if (! $wedding) {
            throw ValidationException::withMessages(['email' => __('validation.custom.camera_no_wedding')]);
        }

        $purchase = $activate->recordManually(
            $wedding,
            CameraTier::from($validated['tier']),
            $request->user(),
            isset($validated['amount']) ? (float) $validated['amount'] : null,
            $validated['note'] ?? null,
        );

        return back()->with('status', __('flash.admin.camera_activated', [
            'wedding' => $wedding->title,
            'tier' => $purchase->tier->label(),
        ]));
    }

    /** Take an album down now: every file goes, as when its time runs out. */
    public function destroy(Request $request, CameraAlbum $album, PurgeCameraAlbum $purge): RedirectResponse
    {
        $purge->handle($album);

        Log::warning('Admin took down a Kamera Majlis album', ['admin_id' => $request->user()->id, 'album_id' => $album->id, 'wedding_id' => $album->wedding_id]);

        return back()->with('status', __('flash.admin.camera_purged', ['wedding' => $album->wedding->title]));
    }

    public function destroyMedia(Request $request, CameraMedia $media, DeleteCameraMedia $delete): RedirectResponse
    {
        $delete->handle($media);

        Log::warning('Admin deleted a reported Kamera Majlis file', ['admin_id' => $request->user()->id, 'media_id' => $media->id, 'album_id' => $media->camera_album_id]);

        return back()->with('status', __('flash.admin.camera_media_deleted'));
    }

    public function dismissReport(CameraMedia $media): RedirectResponse
    {
        $media->update(['reported_at' => null, 'report_reason' => null]);

        return back()->with('status', __('flash.admin.camera_report_dismissed'));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function reported(): array
    {
        return CameraMedia::query()
            ->whereNotNull('reported_at')
            ->with('album.wedding')
            ->oldest('reported_at')
            ->limit(self::REPORTED)
            ->get()
            ->map(fn (CameraMedia $media): array => [
                'id' => $media->id,
                'type' => $media->type->value,
                'url' => $media->url(),
                'thumb' => $media->thumbnailUrl() ?? $media->url(),
                'wedding' => $media->album->wedding->title,
                'by' => $media->uploader_name,
                'reason' => $media->report_reason,
                'reported' => $media->reported_at->diffForHumans(),
                'destroy_url' => route('admin.camera.media.destroy', $media),
                'dismiss_url' => route('admin.camera.media.dismiss', $media),
            ])
            ->all();
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function stateOf(CameraAlbum $album): array
    {
        return match (true) {
            $album->purged_at !== null => ['label' => __('enums.camera_album_filter.purged'), 'tone' => 'muted'],
            $album->isActive() => ['label' => __('enums.camera_album_filter.active'), 'tone' => 'emerald'],
            default => ['label' => __('pages.admin_camera.state_expiring'), 'tone' => 'amber'],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function purgeAction(CameraAlbum $album): array
    {
        return [
            'url' => route('admin.camera.destroy', $album),
            'label' => __('pages.admin_camera.purge'),
            'tone' => 'line',
            'method' => 'DELETE',
            'confirm' => [
                'title' => __('pages.admin_camera.purge_title', ['wedding' => $album->wedding->title]),
                'message' => __('pages.admin_camera.purge_message', ['count' => $album->photos_count + $album->videos_count]),
                'confirmLabel' => __('pages.admin_camera.purge_confirm'),
                'tone' => 'danger',
            ],
        ];
    }
}
