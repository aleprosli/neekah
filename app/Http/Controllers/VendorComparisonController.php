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
        $seo->title('Banding vendor')->noindex();

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
            $this->row('Kategori', $vendors->map(fn (Vendor $v): string => $v->category->icon.' '.$v->category->name)->all()),
            $this->row('Lokasi', $vendors->map(fn (Vendor $v): string => $v->city.', '.$v->state)->all()),
            $this->row('Tahap', $vendors->map(fn (Vendor $v): string => $v->tier->label())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => $v->tier->rank())),
            $this->row('Rating', $vendors->map(fn (Vendor $v): string => $v->reviews_count ? '★ '.number_format($v->rating_avg, 1).' ('.$v->reviews_count.')' : 'Baru')->all(), $this->bestIndex($vendors, fn (Vendor $v): float => (float) $v->rating_avg)),
            $this->row('Harga bermula', $vendors->map(fn (Vendor $v): string => 'RM'.number_format($v->price_from).' / '.$v->price_unit->label())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => -(float) $v->price_from)),
            $this->row('Pakej termurah', $vendors->map(fn (Vendor $v): string => $v->packages->isEmpty() ? '—' : 'RM'.number_format($v->packages->min('price')))->all()),
            $this->row('Bilangan pakej', $vendors->map(fn (Vendor $v): string => (string) $v->packages->count())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => $v->packages->count())),
            $this->row('Majlis selesai', $vendors->map(fn (Vendor $v): string => (string) $v->completed_bookings_count)->all(), $this->bestIndex($vendors, fn (Vendor $v): float => $v->completed_bookings_count)),
            $this->row('Completion rate', $vendors->map(fn (Vendor $v): string => $v->completion_rate.'%')->all(), $this->bestIndex($vendors, fn (Vendor $v): float => $v->completion_rate)),
            $this->row('Response rate', $vendors->map(fn (Vendor $v): string => $v->responseRateLabel())->all(), $this->bestIndex($vendors, fn (Vendor $v): float => (float) ($v->response_rate ?? 0))),
            $this->row('Vendor Score', $vendors->map(fn (Vendor $v): string => number_format((float) $v->score, 1))->all(), $this->bestIndex($vendors, fn (Vendor $v): float => (float) $v->score)),
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
