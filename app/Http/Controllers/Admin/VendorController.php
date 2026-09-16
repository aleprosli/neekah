<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VendorStatus;
use App\Enums\VendorTier;
use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PortfolioItem;
use App\Models\Vendor;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    /** Columns for components/ui/DataTable.vue. */
    private const COLUMNS = [
        ['key' => 'vendor', 'label' => 'Vendor', 'type' => 'html', 'sort' => 'name', 'sortable' => true],
        ['key' => 'category', 'label' => 'Kategori'],
        ['key' => 'location', 'label' => 'Lokasi'],
        ['key' => 'tier', 'label' => 'Tahap'],
        ['key' => 'score', 'label' => 'Score', 'sortable' => true, 'align' => 'right'],
        ['key' => 'status', 'label' => 'Status', 'type' => 'html'],
    ];

    public function index(Request $request): View
    {
        return view('admin.vendors.index', [
            'columns' => self::COLUMNS,
            'status' => VendorStatus::tryFrom($request->string('status')->toString()),
            'counts' => Vendor::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
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

        $vendors = Vendor::query()
            ->with(['category', 'user'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query->where('name', 'like', $like)->orWhere('city', 'like', $like));
            })
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $vendors->getCollection()->map(fn (Vendor $vendor): array => [
                'url' => route('admin.vendors.show', $vendor),
                'vendor' => view('components.admin.vendor-cell', ['vendor' => $vendor])->render(),
                'category' => $vendor->category->name,
                'location' => $vendor->city.', '.$vendor->state,
                'tier' => $vendor->tier->label(),
                'score' => number_format((float) $vendor->score, 1),
                'status' => view('components.admin.status-pill', ['label' => $vendor->status->label(), 'tone' => $vendor->status->tone()])->render(),
                'action' => $vendor->status === VendorStatus::Approved
                    ? ['url' => route('admin.vendors.status', $vendor), 'label' => 'Gantung', 'tone' => 'line', 'fields' => ['status' => VendorStatus::Suspended->value]]
                    : ['url' => route('admin.vendors.status', $vendor), 'label' => 'Lulus', 'tone' => 'brand', 'fields' => ['status' => VendorStatus::Approved->value]],
            ])->all(),
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
                    ])->values(),
                'tiers' => collect(VendorTier::cases())
                    ->map(fn (VendorTier $case): array => ['value' => $case->value, 'label' => $case->label()])
                    ->all(),
            ]),
        ]);
    }
}
