<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ModerateReview;
use App\Actions\SubmitVendorReview;
use App\Enums\ReviewFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdminReviewRequest;
use App\Models\Review;
use App\Models\Vendor;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    /** Columns for components/ui/DataTable.vue. */
    /**
     * A constant cannot hold a function call, and these labels are
     * translated now.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'author', 'label' => __('props.admin.penulis'), 'type' => 'html'],
            ['key' => 'vendor', 'label' => __('props.admin.vendor_3')],
            ['key' => 'rating', 'label' => __('props.admin.bintang'), 'align' => 'right', 'sortable' => true],
            ['key' => 'comment', 'label' => __('props.admin.ulasan')],
            ['key' => 'written', 'label' => __('props.admin.tarikh'), 'sort' => 'created_at', 'sortable' => true],
            ['key' => 'state', 'label' => __('props.admin.status_3'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $filter = ReviewFilter::tryFrom($request->string('filter')->toString());

        return view('admin.reviews.index', [
            'columns' => self::columns(),
            'total' => Review::count(),
            'filters' => [[
                'key' => 'filter',
                'value' => $filter?->value,
                'allLabel' => __('props.common.all'),
                'allCount' => Review::count(),
                'hint' => __('props.admin.review_dari_tempahan_menggerakkan_rating'),
                'options' => array_map(fn (ReviewFilter $case): array => [
                    'value' => $case->value,
                    'label' => $case->label(),
                    'count' => $case->apply(Review::query())->count(),
                    'description' => $case->description(),
                ], ReviewFilter::cases()),
            ]],
            'props' => VueProps::for([
                'action' => route('admin.reviews.store'),
                'vendors' => Vendor::orderBy('name')->get(['id', 'name'])->map(
                    fn (Vendor $vendor): array => ['id' => $vendor->id, 'name' => $vendor->name],
                ),
                'maxPhotos' => Review::MAX_PHOTOS,
            ]),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filter = ReviewFilter::tryFrom($request->string('filter')->toString());
        $sort = in_array($request->string('sort')->toString(), ['rating', 'created_at'], true)
            ? $request->string('sort')->toString()
            : 'created_at';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $matching = Review::query()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('comment', 'like', $like)
                    ->orWhere('author_name', 'like', $like)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($user) => $user->where('name', 'like', $like)));
            });

        $reviews = $matching->clone()
            ->with(['vendor:id,name', 'user:id,name', 'photos'])
            ->when($filter, fn ($query) => $filter->apply($query))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $reviews->getCollection()->map(fn (Review $review): array => [
                'author' => view('components.admin.review-author-cell', ['review' => $review])->render(),
                'vendor' => $review->vendor->name,
                'rating' => str_repeat('★', $review->rating),
                'comment' => str($review->comment)->limit(90)->value(),
                'written' => $review->created_at->translatedFormat('j M Y'),
                'state' => view('components.admin.status-pill', $this->stateOf($review))->render(),
                'actions' => $this->rowActions($review),
            ])->all(),
            'filters' => ['filter' => collect(ReviewFilter::cases())
                ->mapWithKeys(fn (ReviewFilter $case): array => [$case->value => $case->apply($matching->clone())->count()])
                ->prepend($matching->clone()->count(), '')
                ->all()],
            'meta' => [
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ],
        ]);
    }

    /**
     * @return array{label: string, tone: string}
     */
    private function stateOf(Review $review): array
    {
        return match (true) {
            $review->isHidden() => ['label' => __('props.admin.disembunyikan'), 'tone' => 'muted'],
            $review->isReported() => ['label' => __('props.admin.dilaporkan'), 'tone' => 'amber'],
            $review->isVerified() => ['label' => __('props.admin.dari_tempahan'), 'tone' => 'emerald'],
            default => ['label' => __('props.admin.terbuka'), 'tone' => 'sky'],
        };
    }

    /**
     * Hide what is showing, put back what is not — and, once a review is
     * already hidden, the option to erase it for good. Deleting is offered
     * only there on purpose: it takes the photos with it and cannot be undone,
     * so it is never one tap away from a review that is still live.
     *
     * @return array<int, array<string, mixed>>
     */
    private function rowActions(Review $review): array
    {
        if (! $review->isHidden()) {
            return [$this->hideAction($review)];
        }

        return [$this->restoreAction($review), $this->deleteAction($review)];
    }

    /**
     * @return array<string, mixed>
     */
    private function restoreAction(Review $review): array
    {
        return [
            'url' => route('admin.reviews.restore', $review),
            'label' => __('props.admin.paparkan_semula'),
            'tone' => 'brand',
            'confirm' => [
                'title' => __('props.admin.paparkan_semula_review_oleh').$review->authorName().'?',
                'message' => __('props.admin.review_ini_akan_kembali_ke').$review->vendor->name.'.'.($review->isVerified() ? ' Rating dan mata vendor dikira semula.' : ''),
                'confirmLabel' => 'Ya, paparkan',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function hideAction(Review $review): array
    {
        return [
            'url' => route('admin.reviews.hide', $review),
            'label' => __('props.admin.sembunyikan'),
            'tone' => 'line',
            'fields' => ['reason' => 'Disembunyikan oleh admin'],
            'confirm' => [
                'title' => __('props.admin.sembunyikan_review_oleh').$review->authorName().'?',
                'message' => __('props.admin.ia_hilang_dari_profil').$review->vendor->name.' serta-merta. Rekodnya kekal dan anda boleh paparkannya semula.'.($review->isVerified() ? ' Rating dan mata vendor dikira semula.' : ''),
                'confirmLabel' => 'Ya, sembunyikan',
                'tone' => 'danger',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function deleteAction(Review $review): array
    {
        return [
            'url' => route('admin.reviews.destroy', $review),
            'label' => __('props.admin.padam_kekal'),
            'tone' => 'line',
            'method' => 'DELETE',
            'confirm' => [
                'title' => __('props.admin.padam_review_oleh').$review->authorName().' untuk selamanya?',
                'message' => __('props.admin.ulasan_dan_setiap_gambarnya_dipadam'),
                'confirmLabel' => 'Ya, padam kekal',
                'tone' => 'danger',
            ],
        ];
    }

    /**
     * A review an admin carries over from wherever the vendor had it before.
     */
    public function store(StoreAdminReviewRequest $request, SubmitVendorReview $submitReview): RedirectResponse
    {
        $vendor = Vendor::findOrFail($request->integer('vendor_id'));

        $review = $submitReview->handle(
            $vendor,
            $request->safe()->only(['rating', 'comment', 'author_name', 'author_email']),
            $request->file('photos') ?? [],
            addedBy: $request->user(),
        );

        if ($writtenOn = $request->date('written_on')) {
            $review->forceFill(['created_at' => $writtenOn])->save();
        }

        return redirect()
            ->route('admin.reviews.index')
            ->with('status', 'Review oleh '.$review->author_name.' ditambah pada profil '.$vendor->name.'.');
    }

    public function hide(Request $request, Review $review, ModerateReview $moderate): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $moderate->hide($review, $request->user(), $request->string('reason')->limit(200)->toString() ?: 'Disembunyikan oleh admin');

        return back()->with('status', 'Review oleh '.$review->authorName().' disembunyikan.');
    }

    public function restore(Review $review, ModerateReview $moderate): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $moderate->restore($review);

        return back()->with('status', 'Review oleh '.$review->authorName().' dipaparkan semula.');
    }

    public function destroy(Review $review, ModerateReview $moderate): RedirectResponse
    {
        Gate::authorize('moderate', $review);

        $author = $review->authorName();
        $moderate->delete($review);

        return back()->with('status', 'Review oleh '.$author.' dipadam kekal.');
    }
}
