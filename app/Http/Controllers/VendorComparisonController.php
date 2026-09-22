<?php

namespace App\Http\Controllers;

use App\Models\Vendor;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class VendorComparisonController extends Controller
{
    public const MAX_VENDORS = 4;

    /**
     * Compare approved vendors side by side. Selection travels in the query string,
     * so a couple can share the comparison with their partner as a link.
     */
    public function __invoke(Request $request, Seo $seo): View
    {
        $seo->title(__('seo.compare.title'))->noindex();

        $slugs = collect($request->query('vendors'))
            ->flatten()
            ->filter(fn ($slug): bool => is_string($slug) && $slug !== '')
            ->unique()
            ->take(self::MAX_VENDORS);

        $vendors = $slugs->isEmpty()
            ? collect()
            : Vendor::query()
                ->approved()
                ->with(['category', 'packages' => fn ($query) => $query->active()])
                ->whereIn('slug', $slugs)
                ->get()
                ->sortBy(fn (Vendor $vendor): int => $slugs->search($vendor->slug))
                ->values();

        return view('vendors.compare', [
            'vendors' => $vendors,
            'rows' => $this->rows($vendors),
            'sharedCategory' => $vendors->pluck('category_id')->unique()->count() === 1 ? $vendors->first()?->category : null,
        ]);
    }

    /**
     * The comparison grid. Each row returns a display value per vendor plus the
     * key of the best one, so the winner can be highlighted.
     *
     * @param  Collection<int, Vendor>  $vendors
     * @return array<int, array{label: string, values: array<int, string>, best: int|null}>
     */
    private function rows($vendors): array
    {
        if ($vendors->isEmpty()) {
            return [];
        }

        return [
            $this->row(__('pages.compare.kategori'), $vendors->map(fn (Vendor $v): string => $v->category->name)->all()),
            $this->row(__('pages.compare.lokasi'), $vendors->map(fn (Vendor $v): string => $v->city.', '.$v->state)->all()),
            $this->row(__('pages.compare.kawasan_dicover'), $vendors->map(fn (Vendor $v): string => implode(', ', $v->serviceStates()))->all()),
            $this->row(__('pages.compare.tahap'), $vendors->map(fn (Vendor $v): string => $v->tier->label())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => $v->tier->rank())),
            $this->row(__('pages.compare.rating'), $vendors->map(fn (Vendor $v): string => $v->reviews_count ? '★ '.number_format($v->rating_avg, 1).' ('.$v->reviews_count.')' : __('pages.compare.baru'))->all(), $this->bestIndex($vendors, fn (Vendor $v): float => (float) $v->rating_avg)),
            $this->row(__('pages.compare.harga_bermula'), $vendors->map(fn (Vendor $v): string => 'RM'.number_format($v->price_from).' / '.$v->price_unit->label())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => -(float) $v->price_from)),
            $this->row(__('pages.compare.pakej_termurah'), $vendors->map(fn (Vendor $v): string => $v->packages->isEmpty() ? '—' : 'RM'.number_format($v->packages->min('price')))->all()),
            $this->row(__('pages.compare.bilangan_pakej'), $vendors->map(fn (Vendor $v): string => (string) $v->packages->count())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => $v->packages->count())),
            // Completed bookings and completion rate are counted from bookings made
            // through Neekah, and couples deal with vendors directly now, so every
            // vendor would show 0 — a row that says nothing is worse than no row.
            $this->row(__('pages.compare.response_rate'), $vendors->map(fn (Vendor $v): string => $v->responseRateLabel())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => (float) ($v->response_rate ?? 0))),
            $this->row(__('pages.compare.vendor_score'), $vendors->map(fn (Vendor $v): string => number_format((float) $v->score, 1))->all(), $this->bestIndex($vendors, fn (Vendor $v): float => (float) $v->score)),
        ];
    }

    /**
     * @param  array<int, string>  $values
     * @return array{label: string, values: array<int, string>, best: int|null}
     */
    private function row(string $label, array $values, ?int $best = null): array
    {
        return ['label' => $label, 'values' => $values, 'best' => $best];
    }

    /**
     * The index of the highest scoring vendor, or null when they tie.
     *
     * @param  Collection<int, Vendor>  $vendors
     */
    private function bestIndex($vendors, callable $metric): ?int
    {
        $scores = $vendors->map($metric);

        if ($scores->unique()->count() <= 1) {
            return null;
        }

        return (int) $scores->search($scores->max());
    }
}
