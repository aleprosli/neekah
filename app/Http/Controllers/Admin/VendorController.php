<?php

namespace App\Http\Controllers\Admin;

use App\Enums\SubscriptionStatus;
use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VendorController extends Controller
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
            ['key' => 'vendor', 'label' => __('props.admin.vendor_5'), 'type' => 'html', 'sort' => 'name', 'sortable' => true],
            ['key' => 'category', 'label' => __('props.admin.kategori')],
            ['key' => 'location', 'label' => __('props.admin.lokasi')],
            ['key' => 'setup', 'label' => __('props.admin.kelengkapan'), 'type' => 'html'],
            ['key' => 'tier', 'label' => __('props.admin.tahap')],
            ['key' => 'score', 'label' => __('props.admin.score'), 'sortable' => true, 'align' => 'right'],
            ['key' => 'status', 'label' => __('props.admin.status_6'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $counts = Vendor::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.vendors.index', [
            'columns' => self::columns(),
            // Approving a whole batch of new registrations at once: each one
            // still gets its email and its promotion out of New.
            'bulkActions' => [
                [
                    'key' => 'approve',
                    'label' => __('props.admin.luluskan'),
                    'tone' => 'brand',
                    'url' => route('admin.vendors.bulk-status'),
                    'fields' => ['status' => VendorStatus::Approved->value],
                    'confirm' => [
                        'title' => __('props.admin.luluskan_count_vendor'),
                        'message' => __('props.admin.setiap_profil_akan_dipaparkan_di'),
                        'confirmLabel' => __('flash.confirm.yes_approve_count'),
                    ],
                ],
                [
                    'key' => 'reject',
                    'label' => __('props.admin.tolak'),
                    'tone' => 'danger',
                    'url' => route('admin.vendors.bulk-status'),
                    'fields' => ['status' => VendorStatus::Rejected->value],
                    'confirm' => [
                        'title' => __('props.admin.tolak_count_permohonan'),
                        'message' => __('props.admin.setiap_vendor_menerima_emel_penolakan'),
                        'confirmLabel' => __('flash.confirm.yes_reject_count'),
                        'tone' => 'danger',
                    ],
                ],
                [
                    'key' => 'suspend',
                    'label' => __('props.admin.gantung'),
                    'tone' => 'line',
                    'url' => route('admin.vendors.bulk-status'),
                    'fields' => ['status' => VendorStatus::Suspended->value],
                    'confirm' => [
                        'title' => __('props.admin.gantung_count_vendor'),
                        'message' => __('props.admin.profil_mereka_akan_hilang_dari'),
                        'confirmLabel' => __('flash.confirm.yes_suspend_count'),
                        'tone' => 'danger',
                    ],
                ],
            ],
            // The chips belong to the table, which swaps its rows in place; as
            // links they reloaded the page and collided with its paging.
            'filters' => [
                [
                    'key' => 'status',
                    'value' => VendorStatus::tryFrom($request->string('status')->toString())?->value,
                    'allLabel' => __('props.common.all'),
                    'allCount' => $counts->sum(),
                    'options' => array_map(fn (VendorStatus $case): array => [
                        'value' => $case->value,
                        'label' => $case->label(),
                        'count' => $counts[$case->value] ?? 0,
                    ], VendorStatus::cases()),
                ],
                [
                    'key' => 'setup',
                    'label' => __('props.admin.kelengkapan_2'),
                    'value' => in_array($request->string('setup')->toString(), ['complete', 'partial'], true)
                        ? $request->string('setup')->toString()
                        : null,
                    'allLabel' => __('props.common.all'),
                    'allCount' => Vendor::count(),
                    'hint' => __('props.admin.lengkap_bermaksud_profil_penuh_sekurang'),
                    'options' => [
                        ['value' => 'complete', 'label' => __('props.admin.setup_lengkap'), 'count' => Vendor::setupComplete()->count()],
                        ['value' => 'partial', 'label' => __('props.admin.setup_belum_lengkap'), 'count' => Vendor::whereNot(fn (Builder $query) => $query->setupComplete())->count()],
                    ],
                ],
            ],
        ]);
    }

    /**
     * The list the admin is looking at, as a CSV: the same filters, the same
     * search, and the phone numbers the table has no room for. Streamed and
     * chunked, so exporting every vendor costs one row of memory at a time.
     */
    public function export(Request $request): StreamedResponse
    {
        $status = VendorStatus::tryFrom($request->string('status')->toString());
        $setup = $request->string('setup')->toString();

        $vendors = $this->withSetup($this->searched($request), $setup)
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->with(['category', 'user'])
            ->withCount([
                'packages as active_packages_count' => fn ($packages) => $packages->where('is_active', true),
                'portfolioItems',
            ]);

        $name = collect([
            'neekah-vendor',
            $status?->value,
            match ($setup) {
                'complete' => 'setup-lengkap',
                'partial' => 'setup-belum-lengkap',
                default => null,
            },
            now()->format('Y-m-d'),
        ])->filter()->implode('-').'.csv';

        return response()->streamDownload(function () use ($vendors): void {
            $handle = fopen('php://output', 'wb');

            // Excel reads a CSV as the local codepage unless it is told
            // otherwise, which turns every Malay name with an accent to mojibake.
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Nama', 'Kategori', 'Telefon', 'WhatsApp', 'Emel', 'Bandar', 'Negeri',
                'Status', 'Tahap', 'Kelengkapan', 'Pakej aktif', 'Gambar portfolio', 'Daftar', 'Profil',
            ]);

            $vendors->chunkById(500, function ($chunk) use ($handle): void {
                foreach ($chunk as $vendor) {
                    fputcsv($handle, [
                        $vendor->name,
                        $vendor->category->name,
                        $vendor->phone,
                        $vendor->whatsapp ?: $vendor->phone,
                        $vendor->user->email,
                        $vendor->city,
                        $vendor->state,
                        $vendor->status->label(),
                        $vendor->tier->label(),
                        $vendor->hasCompleteProfile() && $vendor->hasCompleteCatalogue() ? 'Lengkap' : __('props.admin.belum_lengkap'),
                        $vendor->active_packages_count,
                        $vendor->portfolio_items_count,
                        $vendor->created_at->toDateString(),
                        route('admin.vendors.show', $vendor),
                    ]);
                }
            });

            fclose($handle);
        }, $name, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * A page of vendors, each carrying the one status change that makes sense
     * for it: approve what is not approved, suspend what is.
     */
    public function data(Request $request): JsonResponse
    {
        $status = VendorStatus::tryFrom($request->string('status')->toString());
        $sort = in_array($request->string('sort')->toString(), ['name', 'score'], true)
            ? $request->string('sort')->toString()
            : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $setup = $request->string('setup')->toString();

        $matching = fn (): Builder => $this->searched($request);
        $withSetup = fn (Builder $query): Builder => $this->withSetup($query, $setup);

        $countingSetup = $matching()->when($status, fn (Builder $query) => $query->where('status', $status));

        $vendors = $withSetup($countingSetup->clone())
            ->with(['category', 'user'])
            // The two counts the setup column and hasCompleteCatalogue() read,
            // so a page of vendors costs two queries rather than one each.
            ->withCount([
                'packages as active_packages_count' => fn ($packages) => $packages->where('is_active', true),
                'portfolioItems',
            ])
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $vendors->getCollection()->map(fn (Vendor $vendor): array => [
                // The id is what a batch action posts back.
                'id' => $vendor->id,
                'url' => route('admin.vendors.show', $vendor),
                'vendor' => view('components.admin.vendor-cell', ['vendor' => $vendor])->render(),
                'category' => $vendor->category->name,
                'location' => $vendor->city.', '.$vendor->state,
                'setup' => view('components.admin.vendor-setup', ['vendor' => $vendor])->render(),
                'tier' => $vendor->tier->label(),
                'score' => number_format((float) $vendor->score, 1),
                'status' => view('components.admin.status-pill', ['label' => $vendor->status->label(), 'tone' => $vendor->status->tone()])->render(),
                // Both of these email the vendor and change what the marketplace
                // shows, so neither goes through on a single stray tap.
                'action' => $vendor->status === VendorStatus::Approved
                    ? [
                        'url' => route('admin.vendors.status', $vendor),
                        'label' => __('props.admin.gantung_2'),
                        'tone' => 'line',
                        'fields' => ['status' => VendorStatus::Suspended->value],
                        'confirm' => [
                            'title' => __('props.admin.gantung_3').$vendor->name.'?',
                            'message' => __('props.admin.profil_ini_akan_hilang_dari'),
                            'confirmLabel' => __('flash.confirm.yes_suspend'),
                            'tone' => 'danger',
                        ],
                    ]
                    : [
                        'url' => route('admin.vendors.status', $vendor),
                        'label' => __('props.admin.lulus'),
                        'tone' => 'brand',
                        'fields' => ['status' => VendorStatus::Approved->value],
                        'confirm' => [
                            'title' => __('props.admin.luluskan_2').$vendor->name.'?',
                            'message' => __('props.admin.profil_ini_akan_dipaparkan_di'),
                            'confirmLabel' => __('flash.confirm.yes_approve'),
                        ],
                    ],
            ])->all(),
            // The chips follow the filters: each group counts what the others
            // left, so a count can never disagree with the table under it.
            'filters' => [
                'status' => TableFilter::countsByColumn($withSetup($matching()), 'status'),
                'setup' => [
                    '' => $countingSetup->clone()->count(),
                    'complete' => $countingSetup->clone()->setupComplete()->count(),
                    'partial' => $countingSetup->clone()->whereNot(fn (Builder $inner) => $inner->setupComplete())->count(),
                ],
            ],
            'meta' => [
                'total' => $vendors->total(),
                'per_page' => $vendors->perPage(),
                'current_page' => $vendors->currentPage(),
                'last_page' => $vendors->lastPage(),
            ],
        ]);
    }

    public function show(Vendor $vendor): View
    {
        $vendor->load(['category', 'categories', 'user', 'packages', 'portfolioItems']);

        return view('admin.vendors.show', [
            'vendor' => $vendor,
            // Unpaid checkouts are abandoned carts; the history is what was paid.
            'subscriptions' => $vendor->subscriptions()->with('addedBy')->where('status', '!=', SubscriptionStatus::Pending)->limit(20)->get(),
            'props' => VueProps::for([
                'vendor' => [
                    'status' => $vendor->status->label(),
                    'tier' => $vendor->tier->value,
                    'tier_locked' => (bool) $vendor->tier_locked,
                    'tier_url' => route('admin.vendors.tier', $vendor),
                ],
                'facts' => [
                    ['label' => __('props.admin.pemilik'), 'value' => $vendor->user->name, 'detail' => collect([$vendor->user->email, $vendor->user->phone])->filter()->implode(' · ')],
                    ['label' => __('props.admin.didaftar'), 'value' => $vendor->created_at->translatedFormat('j M Y')],
                    ['label' => __('props.admin.kategori_2'), 'value' => $vendor->category->name, 'detail' => $vendor->extraCategories()->pluck('name')->implode(' · ') ?: null],
                    ['label' => __('props.admin.kawasan_perkhidmatan'), 'value' => implode(' · ', $vendor->serviceStates())],
                    ['label' => __('props.admin.rating'), 'value' => '★ '.number_format((float) $vendor->rating_avg, 2).' ('.$vendor->reviews_count.' review)'],
                    ['label' => __('props.admin.booking_2'), 'value' => __('props.units.total_completed', ['total' => $vendor->bookings()->count(), 'completed' => $vendor->completed_bookings_count])],
                    ['label' => __('props.admin.harga_bermula'), 'value' => 'RM'.number_format((float) $vendor->price_from, 2).' / '.$vendor->price_unit->label()],
                    ['label' => __('props.admin.vendor_score'), 'value' => number_format((float) $vendor->score, 2).($vendor->tier_locked ? ' · tahap dikunci' : '')],
                    ['label' => __('props.admin.performance_points'), 'value' => number_format($vendor->points_total).($vendor->penalty_points ? ' '.__('props.units.penalties', ['count' => $vendor->penalty_points]) : '')],
                    ['label' => __('props.admin.completion_rate'), 'value' => $vendor->completion_rate.'% · response '.$vendor->responseRateLabel()],
                    ...($vendor->tagline ? [['label' => __('props.admin.tagline_2'), 'value' => $vendor->tagline, 'wide' => true]] : []),
                    ...($vendor->description ? [['label' => __('props.admin.penerangan'), 'value' => $vendor->description, 'wide' => true]] : []),
                ],
                'packages' => $vendor->packages->map(fn (Package $package): array => [
                    'name' => $package->name,
                    'duration' => $package->duration,
                    'price' => 'RM'.number_format((float) $package->price, 2),
                ])->values(),
                'portfolio' => $vendor->portfolioItems->take(10)->map(fn (PortfolioItem $item): array => [
                    'url' => $item->url(),
                    'thumbnail' => $item->thumbnailUrl(),
                ])->values(),
                'statusActions' => collect(VendorStatus::cases())
                    ->reject(fn (VendorStatus $case): bool => $case === $vendor->status)
                    ->map(fn (VendorStatus $case): array => [
                        'value' => $case->value,
                        'label' => $case->label(),
                        'url' => route('admin.vendors.status', $vendor),
                        'primary' => $case === VendorStatus::Approved,
                        'tone' => in_array($case, [VendorStatus::Suspended, VendorStatus::Rejected], true) ? 'danger' : 'brand',
                        'confirm_title' => $this->statusQuestion($case, $vendor),
                        'confirm_message' => $this->statusConsequence($case),
                    ])->values(),
                'tiers' => collect(VendorTier::cases())
                    ->map(fn (VendorTier $case): array => ['value' => $case->value, 'label' => $case->label()])
                    ->all(),
            ]),
        ]);
    }

    /**
     * What the admin is about to do to this vendor, in one sentence. Every
     * status change emails them and moves their profile on or off the
     * marketplace, so the dialog names the vendor rather than asking "Are you
     * sure?" about nothing in particular.
     */
    /**
     * The vendors the admin is looking at: the search only, so each chip group
     * can be counted against what the others left.
     *
     * @return Builder<Vendor>
     */
    private function searched(Request $request): Builder
    {
        return Vendor::query()
            ->when($request->string('search')->trim()->toString(), function (Builder $query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn (Builder $query) => $query->where('name', 'like', $like)->orWhere('city', 'like', $like));
            });
    }

    /**
     * @param  Builder<Vendor>  $query
     * @return Builder<Vendor>
     */
    private function withSetup(Builder $query, string $setup): Builder
    {
        return $query
            ->when($setup === 'complete', fn (Builder $query) => $query->setupComplete())
            ->when($setup === 'partial', fn (Builder $query) => $query->whereNot(fn (Builder $inner) => $inner->setupComplete()));
    }

    private function statusQuestion(VendorStatus $status, Vendor $vendor): string
    {
        return match ($status) {
            VendorStatus::Approved => 'Luluskan '.$vendor->name.'?',
            VendorStatus::Suspended => 'Gantung '.$vendor->name.'?',
            VendorStatus::Rejected => 'Tolak permohonan '.$vendor->name.'?',
            VendorStatus::Pending => 'Kembalikan '.$vendor->name.' ke status menunggu?',
        };
    }

    private function statusConsequence(VendorStatus $status): string
    {
        return match ($status) {
            VendorStatus::Approved => __('props.admin.vendor_approve'),
            VendorStatus::Suspended => __('props.admin.vendor_suspend'),
            VendorStatus::Rejected => __('props.admin.vendor_reject'),
            VendorStatus::Pending => __('props.admin.vendor_unapprove'),
        };
    }
}
