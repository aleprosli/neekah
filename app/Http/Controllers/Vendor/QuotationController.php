<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\DuplicateQuotation;
use App\Actions\IssueQuotationInvoice;
use App\Actions\SaveQuotation;
use App\Actions\SendQuotation;
use App\Enums\DepositType;
use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Enums\VendorFeature;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveQuotationRequest;
use App\Models\Contract;
use App\Models\Enquiry;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\Vendor;
use App\Support\PhoneNumber;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Neekah Pro: quotations the vendor writes, shares by link, and once accepted
 * issues again as an invoice.
 */
class QuotationController extends Controller
{
    /**
     * Columns for components/ui/DataTable.vue, matching the keys data() returns.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'number', 'label' => __('pages.quotations.col_number'), 'sortable' => true, 'sort' => 'id'],
            ['key' => 'client', 'label' => __('pages.quotations.col_client')],
            ['key' => 'event_date', 'label' => __('pages.quotations.col_event_date'), 'sortable' => true],
            ['key' => 'total', 'label' => __('pages.quotations.col_total'), 'sort' => 'total', 'sortable' => true, 'align' => 'right'],
            ['key' => 'valid_until', 'label' => __('pages.quotations.col_valid_until')],
            ['key' => 'status', 'label' => __('pages.quotations.col_status'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.quotations.index', [
            'columns' => self::columns(),
            'filters' => [TableFilter::fromEnum(
                'status',
                QuotationStatus::cases(),
                TableFilter::requested($request, 'status', array_column(QuotationStatus::cases(), 'value')),
                $vendor->quotations()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
                label: __('props.common.filter_status'),
            )],
        ]);
    }

    /**
     * A page of this vendor's quotations, filtered and sorted in the database.
     */
    public function data(Request $request): JsonResponse
    {
        $statuses = TableFilter::requestedEnums($request, 'status', QuotationStatus::class);
        $sort = in_array($request->string('sort')->toString(), ['id', 'event_date', 'total'], true)
            ? $request->string('sort')->toString()
            : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $matching = $request->user()->vendor->quotations()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query
                    ->where('number', 'like', $like)
                    ->orWhere('invoice_number', 'like', $like)
                    ->orWhere('client_name', 'like', $like));
            });

        $quotations = $matching->clone()
            ->when($statuses, fn ($query) => $query->whereIn('status', $statuses))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'data' => $quotations->getCollection()->map(fn (Quotation $quotation): array => [
                'url' => route('vendor.quotations.show', $quotation),
                'number' => $quotation->number.($quotation->invoice_number ? ' · '.$quotation->invoice_number : ''),
                'client' => $quotation->client_name,
                'event_date' => $quotation->event_date?->translatedFormat('j M Y') ?? '—',
                'total' => Quotation::money($quotation->total),
                'valid_until' => $quotation->valid_until->translatedFormat('j M Y'),
                'status' => view('components.quotation-status', ['quotation' => $quotation])->render(),
            ])->all(),
            'filters' => ['status' => TableFilter::countsByColumn($matching, 'status')],
            'meta' => [
                'total' => $quotations->total(),
                'per_page' => $quotations->perPage(),
                'current_page' => $quotations->currentPage(),
                'last_page' => $quotations->lastPage(),
            ],
        ]);
    }

    /**
     * A blank quotation, or one started from an enquiry: the couple, their
     * date and the package they asked about are filled in.
     */
    public function create(Request $request): View
    {
        $vendor = $request->user()->vendor;
        $enquiry = $request->filled('enquiry')
            ? $vendor->enquiries()->with(['user', 'package', 'wedding'])->find($request->integer('enquiry'))
            : null;

        return view('vendor.quotations.form', [
            'heading' => __('pages.quotations.create_heading'),
            'props' => $this->formProps($vendor, null, $enquiry, route('vendor.quotations.store')),
        ]);
    }

    public function store(SaveQuotationRequest $request, SaveQuotation $save): RedirectResponse
    {
        $quotation = $save->handle($request->user()->vendor, $request->quotationData(), saveTermsAsDefault: $request->boolean('save_terms_as_default'));

        return redirect()
            ->route('vendor.quotations.show', $quotation)
            ->with('status', __('flash.vendor.quotation_saved', ['number' => $quotation->number]));
    }

    public function show(Quotation $quotation): View
    {
        Gate::authorize('view', $quotation);

        $quotation->load(['items', 'booking.payments', 'enquiry', 'contracts', 'vendor.user']);

        return view('vendor.quotations.show', [
            'quotation' => $quotation,
            'props' => VueProps::for(['quotation' => $this->detail($quotation)]),
        ]);
    }

    public function edit(Request $request, Quotation $quotation): View
    {
        Gate::authorize('update', $quotation);

        $quotation->load('items');

        return view('vendor.quotations.form', [
            'heading' => __('pages.quotations.edit_heading', ['number' => $quotation->number]),
            'props' => $this->formProps($request->user()->vendor, $quotation, null, route('vendor.quotations.update', $quotation)),
        ]);
    }

    public function update(SaveQuotationRequest $request, Quotation $quotation, SaveQuotation $save): RedirectResponse
    {
        $save->handle($request->user()->vendor, $request->quotationData(), $quotation, $request->boolean('save_terms_as_default'));

        return redirect()
            ->route('vendor.quotations.show', $quotation)
            ->with('status', __('flash.vendor.quotation_saved', ['number' => $quotation->number]));
    }

    public function destroy(Quotation $quotation): RedirectResponse
    {
        Gate::authorize('delete', $quotation);

        $quotation->delete();

        return redirect()
            ->route('vendor.quotations.index')
            ->with('status', __('flash.vendor.quotation_deleted', ['number' => $quotation->number]));
    }

    /** Open it to the client, and email them when there is an address. */
    public function send(Quotation $quotation, SendQuotation $send): RedirectResponse
    {
        Gate::authorize('update', $quotation);

        $send->handle($quotation);

        return back()->with('status', __(filled($quotation->client_email) ? 'flash.vendor.quotation_emailed' : 'flash.vendor.quotation_sent', ['number' => $quotation->number]));
    }

    public function duplicate(Quotation $quotation, DuplicateQuotation $duplicate): RedirectResponse
    {
        Gate::authorize('view', $quotation);

        $copy = $duplicate->handle($quotation->load(['items', 'vendor']));

        return redirect()
            ->route('vendor.quotations.edit', $copy)
            ->with('status', __('flash.vendor.quotation_duplicated', ['number' => $copy->number, 'from' => $quotation->number]));
    }

    public function invoice(Quotation $quotation, IssueQuotationInvoice $issue): RedirectResponse
    {
        Gate::authorize('invoice', $quotation);

        $quotation = $issue->handle($quotation);

        return back()->with('status', __('flash.vendor.invoice_issued', ['number' => $quotation->invoice_number]));
    }

    /** What the vendor says has been paid on the invoice: a label, not a ledger. */
    public function invoiceStatus(Request $request, Quotation $quotation): RedirectResponse
    {
        Gate::authorize('invoice', $quotation);
        abort_unless($quotation->isInvoiced(), 404);

        $validated = $request->validate(['invoice_status' => ['required', Rule::enum(InvoiceStatus::class)]]);
        $quotation->update($validated);

        return back()->with('status', __('flash.vendor.invoice_status_saved'));
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Vendor $vendor, ?Quotation $quotation, ?Enquiry $enquiry, string $action): array
    {
        $settings = $vendor->bookingSettingsOrDefault();
        $packages = $vendor->packages()->active()->get();

        $items = $quotation
            ? $quotation->items->map(fn (QuotationItem $item): array => [
                'package_id' => $item->package_id,
                'name' => $item->name,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->values()->all()
            : [];

        if ($items === [] && $enquiry?->package && $enquiry->package->vendor_id === $vendor->id) {
            $items[] = ['package_id' => $enquiry->package->id, 'name' => $enquiry->package->name, 'description' => null, 'quantity' => 1, 'unit_price' => (float) $enquiry->package->price];
        }

        return VueProps::for([
            'action' => $action,
            'method' => $quotation ? 'PUT' : 'POST',
            'number' => $quotation?->number,
            'vendor' => [
                'name' => $vendor->name,
                'logo' => $vendor->logoUrl(),
                'lines' => array_values(array_filter([
                    $vendor->phone,
                    $vendor->user?->email,
                    collect([$vendor->city, $vendor->state])->filter()->implode(', '),
                ])),
            ],
            'cancelUrl' => $quotation ? route('vendor.quotations.show', $quotation) : route('vendor.quotations.index'),
            'packages' => $packages->map(fn (Package $package): array => [
                'id' => $package->id,
                'name' => $package->name,
                'price' => (float) $package->price,
            ])->values(),
            'quotation' => [
                'enquiry_id' => $quotation?->enquiry_id ?? $enquiry?->id,
                'client_name' => $quotation?->client_name ?? $enquiry?->user->name ?? '',
                'client_phone' => $quotation?->client_phone ?? $enquiry?->user->phone ?? '',
                'client_email' => $quotation?->client_email ?? $enquiry?->user->email ?? '',
                'event_date' => ($quotation?->event_date ?? $enquiry?->event_date)?->toDateString() ?? '',
                'event_location' => $quotation?->event_location ?? ($enquiry?->wedding ? trim($enquiry->wedding->city.', '.$enquiry->wedding->state, ', ') : ''),
                'valid_until' => ($quotation?->valid_until ?? today()->addDays(Quotation::DEFAULT_VALID_DAYS))->toDateString(),
                'discount_type' => ($quotation?->discount_type ?? DepositType::Fixed)->value,
                'discount_value' => (float) ($quotation?->discount_value ?? 0),
                'deposit_type' => ($quotation?->deposit_type ?? $settings->deposit_type)->value,
                'deposit_value' => (float) ($quotation?->deposit_value ?? $settings->deposit_value),
                'terms' => $quotation ? (string) $quotation->terms : (string) ($settings->quotation_terms ?? $settings->deposit_terms),
                'notes' => (string) ($quotation?->notes ?? ''),
                'items' => $items,
            ],
            'old' => old(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Quotation $quotation): array
    {
        $share = __('pages.quotations.whatsapp_message', [
            'name' => $quotation->client_name,
            'number' => $quotation->number,
            'vendor' => $quotation->vendor->name,
            'url' => $quotation->publicUrl(),
        ]);
        $phone = PhoneNumber::normalise($quotation->client_phone);
        $canUpdate = request()->user()->can('update', $quotation);
        $canInvoice = request()->user()->can('invoice', $quotation);

        return [
            'number' => $quotation->number,
            'client' => ['name' => $quotation->client_name],
            'history' => collect([
                [__('pages.quotations.history_created'), $quotation->created_at],
                [__('pages.quotations.history_sent'), $quotation->sent_at],
                [__('pages.quotations.history_viewed'), $quotation->viewed_at],
                [__('pages.quotations.history_accepted', ['name' => $quotation->accepted_name]), $quotation->accepted_at],
                [__('pages.quotations.history_declined'), $quotation->declined_at],
                [__('pages.quotations.history_invoiced', ['number' => $quotation->invoice_number]), $quotation->invoiced_at],
            ])->filter(fn (array $entry): bool => $entry[1] !== null)
                ->map(fn (array $entry): array => ['label' => $entry[0], 'at' => $entry[1]->translatedFormat('j M Y, g:i A')])
                ->values(),
            'decline_reason' => $quotation->decline_reason,
            'invoice' => $quotation->isInvoiced() ? [
                'number' => $quotation->invoice_number,
                'status' => $quotation->invoice_status->value,
                'statuses' => collect(InvoiceStatus::cases())->map(fn (InvoiceStatus $status): array => ['value' => $status->value, 'label' => $status->label()])->values(),
                'status_url' => route('vendor.quotations.invoice-status', $quotation),
            ] : null,
            'links' => [
                'public' => $quotation->publicUrl(),
                'print' => $quotation->publicUrl().'?cetak=1',
                'whatsapp' => 'https://wa.me/'.($phone ?? '').'?text='.rawurlencode($share),
                'edit' => $canUpdate ? route('vendor.quotations.edit', $quotation) : null,
                'send' => $canUpdate ? route('vendor.quotations.send', $quotation) : null,
                'duplicate' => route('vendor.quotations.duplicate', $quotation),
                'destroy' => request()->user()->can('delete', $quotation) ? route('vendor.quotations.destroy', $quotation) : null,
                'invoice' => $canInvoice && ! $quotation->isInvoiced() ? route('vendor.quotations.invoice', $quotation) : null,
                'record_booking' => $canInvoice && ! $quotation->booking_id ? route('vendor.bookings.create', ['quotation' => $quotation->token]) : null,
                'booking' => $quotation->booking ? route('vendor.bookings.show', $quotation->booking) : null,
                'enquiry' => $quotation->enquiry ? route('vendor.enquiries.show', $quotation->enquiry) : null,
                'contract' => request()->user()->vendor->hasFeature(VendorFeature::Contracts) ? route('vendor.contracts.create', ['quotation' => $quotation->token]) : null,
            ],
            'contracts' => $quotation->contracts->sortByDesc('id')->map(fn (Contract $contract): array => [
                'number' => $contract->number,
                'status' => $contract->status->label(),
                'url' => route('vendor.contracts.show', $contract),
            ])->values(),
            'is_draft' => $quotation->status === QuotationStatus::Draft,
            'has_email' => filled($quotation->client_email),
        ];
    }
}
