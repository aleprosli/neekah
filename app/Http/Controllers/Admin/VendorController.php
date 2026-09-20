<?php

namespace App\Http\Controllers\Admin;

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
    private const COLUMNS = [
        ['key' => 'vendor', 'label' => 'Vendor', 'type' => 'html', 'sort' => 'name', 'sortable' => true],
        ['key' => 'category', 'label' => 'Kategori'],
        ['key' => 'location', 'label' => 'Lokasi'],
        ['key' => 'setup', 'label' => 'Kelengkapan', 'type' => 'html'],
        ['key' => 'tier', 'label' => 'Tahap'],
        ['key' => 'score', 'label' => 'Score', 'sortable' => true, 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'html'],
    ];

    public function index(Request $request): View
    {
        $counts = Vendor::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.vendors.index', [
            'columns' => self::COLUMNS,
            // Approving a whole batch of new registrations at once: each one
            // still gets its email and its promotion out of New.
            'bulkActions' => [
                [
                    'key' => 'approve',
                    'label' => 'Luluskan',
                    'tone' => 'brand',
                    'url' => route('admin.vendors.bulk-status'),
                    'fields' => ['status' => VendorStatus::Approved->value],
                    'confirm' => [
                        'title' => 'Luluskan __COUNT__ vendor?',
                        'message' => 'Setiap profil akan dipaparkan di marketplace dan setiap vendor menerima emel kelulusan.',
                        'confirmLabel' => 'Ya, luluskan __COUNT__',
                    ],
                ],
                [
                    'key' => 'reject',
                    'label' => 'Tolak',
                    'tone' => 'danger',
                    'url' => route('admin.vendors.bulk-status'),
                    'fields' => ['status' => VendorStatus::Rejected->value],
                    'confirm' => [
                        'title' => 'Tolak __COUNT__ permohonan?',
                        'message' => 'Setiap vendor menerima emel penolakan. Mereka masih boleh melengkapkan profil dan memohon semula.',
                        'confirmLabel' => 'Ya, tolak __COUNT__',
                        'tone' => 'danger',
                    ],
                ],
                [
                    'key' => 'suspend',
                    'label' => 'Gantung',
                    'tone' => 'line',
                    'url' => route('admin.vendors.bulk-status'),
                    'fields' => ['status' => VendorStatus::Suspended->value],
                    'confirm' => [
                        'title' => 'Gantung __COUNT__ vendor?',
                        'message' => 'Profil mereka akan hilang dari marketplace dan setiap vendor menerima emel pemberitahuan.',
                        'confirmLabel' => 'Ya, gantung __COUNT__',
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
                    'allLabel' => 'Semua',
                    'allCount' => $counts->sum(),
                    'options' => array_map(fn (VendorStatus $case): array => [
                        'value' => $case->value,
                        'label' => $case->label(),
                        'count' => $counts[$case->value] ?? 0,
                    ], VendorStatus::cases()),
                ],
                [
                    'key' => 'setup',
                    'label' => 'Kelengkapan',
                    'value' => in_array($request->string('setup')->toString(), ['complete', 'partial'], true)
                        ? $request->string('setup')->toString()
                        : null,
                    'allLabel' => 'Semua',
                    'allCount' => Vendor::count(),
                    'hint' => 'Lengkap bermaksud profil penuh, sekurang-kurangnya satu pakej aktif dan tiga gambar portfolio.',
                    'options' => [
                        ['value' => 'complete', 'label' => 'Setup lengkap', 'count' => Vendor::setupComplete()->count()],
                        ['value' => 'partial', 'label' => 'Setup belum lengkap', 'count' => Vendor::whereNot(fn (Builder $query) => $query->setupComplete())->count()],
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
                        $vendor->hasCompleteProfile() && $vendor->hasCompleteCatalogue() ? 'Lengkap' : 'Belum lengkap',
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
                        'label' => 'Gantung',
                        'tone' => 'line',
                        'fields' => ['status' => VendorStatus::Suspended->value],
                        'confirm' => [
                            'title' => 'Gantung '.$vendor->name.'?',
                            'message' => 'Profil ini akan hilang dari marketplace dan vendor akan menerima emel pemberitahuan.',
                            'confirmLabel' => 'Ya, gantung',
                            'tone' => 'danger',
                        ],
                    ]
                    : [
                        'url' => route('admin.vendors.status', $vendor),
                        'label' => 'Lulus',
                        'tone' => 'brand',
                        'fields' => ['status' => VendorStatus::Approved->value],
                        'confirm' => [
                            'title' => 'Luluskan '.$vendor->name.'?',
                            'message' => 'Profil ini akan dipaparkan di marketplace dan vendor akan menerima emel kelulusan.',
                            'confirmLabel' => 'Ya, luluskan',
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
        $vendor->load(['category', 'user', 'packages', 'portfolioItems']);

        return view('admin.vendors.show', [
            'vendor' => $vendor,
            'props' => VueProps::for([
                'vendor' => [
                    'status' => $vendor->status->label(),
                    'tier' => $vendor->tier->value,
                    'tier_locked' => (bool) $vendor->tier_locked,
                    'tier_url' => route('admin.vendors.tier', $vendor),
                ],
                'facts' => [
                    ['label' => 'Pemilik', 'value' => $vendor->user->name, 'detail' => collect([$vendor->user->email, $vendor->user->phone])->filter()->implode(' · ')],
                    ['label' => 'Didaftar', 'value' => $vendor->created_at->translatedFormat('j M Y')],
                    ['label' => 'Rating', 'value' => '★ '.number_format((float) $vendor->rating_avg, 2).' ('.$vendor->reviews_count.' review)'],
                    ['label' => 'Booking', 'value' => $vendor->bookings()->count().' jumlah · '.$vendor->completed_bookings_count.' selesai'],
                    ['label' => 'Harga bermula', 'value' => 'RM'.number_format((float) $vendor->price_from, 2).' / '.$vendor->price_unit->label()],
                    ['label' => 'Vendor Score', 'value' => number_format((float) $vendor->score, 2).($vendor->tier_locked ? ' · tahap dikunci' : '')],
                    ['label' => 'Performance points', 'value' => number_format($vendor->points_total).($vendor->penalty_points ? ' − '.$vendor->penalty_points.' penalti' : '')],
                    ['label' => 'Completion rate', 'value' => $vendor->completion_rate.'% · response '.$vendor->responseRateLabel()],
                    ...($vendor->tagline ? [['label' => 'Tagline', 'value' => $vendor->tagline, 'wide' => true]] : []),
                    ...($vendor->description ? [['label' => 'Penerangan', 'value' => $vendor->description, 'wide' => true]] : []),
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
            VendorStatus::Approved => 'Profil akan dipaparkan di marketplace dan vendor menerima emel kelulusan.',
            VendorStatus::Suspended => 'Profil akan hilang dari marketplace dan vendor menerima emel pemberitahuan.',
            VendorStatus::Rejected => 'Vendor menerima emel penolakan. Mereka masih boleh melengkapkan profil dan memohon semula.',
            VendorStatus::Pending => 'Profil akan hilang dari marketplace sehingga diluluskan semula.',
        };
    }
}
