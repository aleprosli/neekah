<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookVendorRequest;
use App\Support\DemoCatalogue;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class VendorController extends Controller
{
    public function __construct(private DemoCatalogue $catalogue) {}

    /**
     * List demo vendors with search, filters, sorting and pagination.
     */
    public function index(Request $request): View
    {
        $filters = [
            'q' => $request->string('q')->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'state' => $request->string('state')->toString() ?: null,
            'min_price' => $request->filled('min_price') ? $request->integer('min_price') : null,
            'max_price' => $request->filled('max_price') ? $request->integer('max_price') : null,
            'min_rating' => $request->filled('min_rating') ? (float) $request->input('min_rating') : null,
            'tier' => $request->string('tier')->toString() ?: null,
            'sort' => array_key_exists($request->string('sort')->toString(), DemoCatalogue::SORTS)
                ? $request->string('sort')->toString()
                : 'recommended',
        ];

        $results = $this->catalogue->search($filters);
        $perPage = 9;
        $page = LengthAwarePaginator::resolveCurrentPage();

        $vendors = new LengthAwarePaginator(
            $results->forPage($page, $perPage)->values(),
            $results->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return view('vendors.index', [
            'vendors' => $vendors,
            'filters' => $filters,
            'categories' => $this->catalogue->categories(),
            'activeCategory' => $filters['category'] ? $this->catalogue->category($filters['category']) : null,
            'states' => DemoCatalogue::STATES,
            'tiers' => DemoCatalogue::TIERS,
            'sorts' => DemoCatalogue::SORTS,
            'activeFilterCount' => count(array_filter([$filters['state'], $filters['min_price'], $filters['max_price'], $filters['min_rating'], $filters['tier']], fn ($value) => $value !== null)),
        ]);
    }

    /**
     * Show a single demo vendor profile with packages and booking form.
     */
    public function show(string $slug): View
    {
        $vendor = $this->catalogue->find($slug) ?? abort(404);

        return view('vendors.show', [
            'vendor' => $vendor,
            'category' => $this->catalogue->category($vendor['category']),
            'related' => $this->catalogue->search(['category' => $vendor['category']])
                ->reject(fn (array $candidate): bool => $candidate['slug'] === $slug)
                ->take(3),
        ]);
    }

    /**
     * Demo booking: validate and flash a confirmation without persisting anything.
     */
    public function book(BookVendorRequest $request, string $slug): RedirectResponse
    {
        $vendor = $this->catalogue->find($slug) ?? abort(404);
        $package = $vendor['packages'][$request->integer('package')];
        $deposit = (int) round($package['price'] * 0.4);

        return redirect()
            ->route('vendors.show', $slug)
            ->with('booking', [
                'vendor' => $vendor['name'],
                'package' => $package['name'],
                'date' => $request->date('event_date')->translatedFormat('j F Y'),
                'total' => $package['price'],
                'deposit' => $deposit,
            ]);
    }
}
