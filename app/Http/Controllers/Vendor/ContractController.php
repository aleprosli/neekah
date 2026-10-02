<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\DuplicateContract;
use App\Actions\SaveContract;
use App\Actions\SendContract;
use App\Actions\VoidContract;
use App\Enums\ContractStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveContractRequest;
use App\Http\Requests\VoidContractRequest;
use App\Models\Contract;
use App\Models\Quotation;
use App\Models\Vendor;
use App\Support\PhoneNumber;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

/**
 * Neekah Pro: contracts the vendor writes and the client signs online.
 */
class ContractController extends Controller
{
    /**
     * Columns for components/ui/DataTable.vue, matching the keys data() returns.
     *
     * @return array<int, array<string, mixed>>
     */
    private static function columns(): array
    {
        return [
            ['key' => 'number', 'label' => __('pages.contracts.col_number'), 'sortable' => true, 'sort' => 'id'],
            ['key' => 'client', 'label' => __('pages.contracts.col_client')],
            ['key' => 'event_date', 'label' => __('pages.contracts.col_event_date'), 'sortable' => true],
            ['key' => 'signed_at', 'label' => __('pages.contracts.col_signed_at')],
            ['key' => 'status', 'label' => __('pages.contracts.col_status'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        $vendor = $request->user()->vendor;

        return view('vendor.contracts.index', [
            'columns' => self::columns(),
            'filters' => [TableFilter::fromEnum(
                'status',
                ContractStatus::cases(),
                TableFilter::requested($request, 'status', array_column(ContractStatus::cases(), 'value')),
                $vendor->contracts()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
                label: __('props.common.filter_status'),
            )],
        ]);
    }

    /**
     * A page of this vendor's contracts, filtered and sorted in the database.
     */
    public function data(Request $request): JsonResponse
    {
        $statuses = TableFilter::requestedEnums($request, 'status', ContractStatus::class);
        $sort = in_array($request->string('sort')->toString(), ['id', 'event_date'], true) ? $request->string('sort')->toString() : 'id';
        $direction = $request->string('direction')->toString() === 'asc' ? 'asc' : 'desc';

        $matching = $request->user()->vendor->contracts()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query->where('number', 'like', $like)->orWhere('client_name', 'like', $like));
            });

        $contracts = $matching->clone()
            ->when($statuses, fn ($query) => $query->whereIn('status', $statuses))
            ->orderBy($sort, $direction)
            ->paginate(min($request->integer('per_page', 15), 100));

        return response()->json([
            'data' => $contracts->getCollection()->map(fn (Contract $contract): array => [
                'url' => route('vendor.contracts.show', $contract),
                'number' => $contract->number,
                'client' => $contract->client_name,
                'event_date' => $contract->event_date?->translatedFormat('j M Y') ?? '—',
                'signed_at' => $contract->signed_at?->translatedFormat('j M Y') ?? '—',
                'status' => view('components.booking-status', ['status' => $contract->status])->render(),
            ])->all(),
            'filters' => ['status' => TableFilter::countsByColumn($matching, 'status')],
            'meta' => [
                'total' => $contracts->total(),
                'per_page' => $contracts->perPage(),
                'current_page' => $contracts->currentPage(),
                'last_page' => $contracts->lastPage(),
            ],
        ]);
    }

    /**
     * A blank contract, or one for a quotation: its client and date are
     * filled in and the quotation is attached.
     */
    public function create(Request $request): View
    {
        $vendor = $request->user()->vendor;
        $quotation = $request->filled('quotation') ? $vendor->quotations()->firstWhere('token', $request->string('quotation')->toString()) : null;

        return view('vendor.contracts.form', [
            'heading' => __('pages.contracts.create_heading'),
            'props' => $this->formProps($vendor, null, $quotation, route('vendor.contracts.store')),
        ]);
    }

    public function store(SaveContractRequest $request, SaveContract $save): RedirectResponse
    {
        $contract = $save->handle($request->user()->vendor, $request->contractData(), saveAsDefault: $request->boolean('save_as_default'));

        return redirect()
            ->route('vendor.contracts.show', $contract)
            ->with('status', __('flash.vendor.contract_saved', ['number' => $contract->number]));
    }

    public function show(Contract $contract): View
    {
        Gate::authorize('view', $contract);

        $contract->load(['quotation.items', 'vendor.user']);

        return view('vendor.contracts.show', [
            'contract' => $contract,
            'props' => VueProps::for(['contract' => $this->detail($contract)]),
        ]);
    }

    public function edit(Request $request, Contract $contract): View
    {
        Gate::authorize('update', $contract);

        return view('vendor.contracts.form', [
            'heading' => __('pages.contracts.edit_heading', ['number' => $contract->number]),
            'props' => $this->formProps($request->user()->vendor, $contract, null, route('vendor.contracts.update', $contract)),
        ]);
    }

    public function update(SaveContractRequest $request, Contract $contract, SaveContract $save): RedirectResponse
    {
        $save->handle($request->user()->vendor, $request->contractData(), $contract, $request->boolean('save_as_default'));

        return redirect()
            ->route('vendor.contracts.show', $contract)
            ->with('status', __('flash.vendor.contract_saved', ['number' => $contract->number]));
    }

    public function destroy(Contract $contract): RedirectResponse
    {
        Gate::authorize('delete', $contract);

        $contract->delete();

        return redirect()
            ->route('vendor.contracts.index')
            ->with('status', __('flash.vendor.contract_deleted', ['number' => $contract->number]));
    }

    public function send(Request $request, Contract $contract, SendContract $send): RedirectResponse
    {
        Gate::authorize('send', $contract);

        $send->handle($contract, $request->user()->name);

        return back()->with('status', __(filled($contract->client_email) ? 'flash.vendor.contract_emailed' : 'flash.vendor.contract_sent', ['number' => $contract->number]));
    }

    public function duplicate(Contract $contract, DuplicateContract $duplicate): RedirectResponse
    {
        Gate::authorize('view', $contract);

        $copy = $duplicate->handle($contract->load('vendor'));

        return redirect()
            ->route('vendor.contracts.edit', $copy)
            ->with('status', __('flash.vendor.contract_duplicated', ['number' => $copy->number, 'from' => $contract->number]));
    }

    public function void(VoidContractRequest $request, Contract $contract, VoidContract $void): RedirectResponse
    {
        $void->handle($contract, $request->string('reason')->trim()->toString() ?: null);

        return back()->with('status', __('flash.vendor.contract_voided', ['number' => $contract->number]));
    }

    /**
     * @return array<string, mixed>
     */
    private function formProps(Vendor $vendor, ?Contract $contract, ?Quotation $quotation, string $action): array
    {
        return VueProps::for([
            'action' => $action,
            'method' => $contract ? 'PUT' : 'POST',
            'number' => $contract?->number,
            'vendor' => [
                'name' => $vendor->name,
                'logo' => $vendor->logoUrl(),
            ],
            'cancelUrl' => $contract ? route('vendor.contracts.show', $contract) : route('vendor.contracts.index'),
            'quotations' => $vendor->quotations()->latest('id')->limit(50)->get(['id', 'number', 'client_name'])
                ->map(fn (Quotation $quotation): array => ['value' => $quotation->id, 'label' => $quotation->number.' · '.$quotation->client_name])
                ->values(),
            'contract' => [
                'quotation_id' => $contract?->quotation_id ?? $quotation?->id,
                'client_name' => $contract?->client_name ?? $quotation?->client_name ?? '',
                'client_phone' => $contract?->client_phone ?? $quotation?->client_phone ?? '',
                'client_email' => $contract?->client_email ?? $quotation?->client_email ?? '',
                'event_date' => ($contract?->event_date ?? $quotation?->event_date)?->toDateString() ?? '',
                'sections' => $contract?->sections ?? Contract::defaultSections($vendor->bookingSettingsOrDefault()),
            ],
            'standardKeys' => Contract::SECTION_KEYS,
            'old' => old(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function detail(Contract $contract): array
    {
        $user = request()->user();
        $share = __('pages.contracts.whatsapp_message', [
            'name' => $contract->client_name,
            'number' => $contract->number,
            'vendor' => $contract->vendor->name,
            'url' => $contract->publicUrl(),
        ]);
        $phone = PhoneNumber::normalise($contract->client_phone);

        return [
            'number' => $contract->number,
            'client' => ['name' => $contract->client_name],
            'quotation' => $contract->quotation ? [
                'number' => $contract->quotation->number,
                'url' => route('vendor.quotations.show', $contract->quotation),
            ] : null,
            'content_hash' => $contract->content_hash,
            'history' => collect([
                [__('pages.contracts.history_created'), $contract->created_at],
                [__('pages.contracts.history_sent', ['name' => $contract->vendor_signatory]), $contract->sent_at],
                [__('pages.contracts.history_viewed'), $contract->viewed_at],
                [__('pages.contracts.history_signed', ['name' => $contract->signer_name]), $contract->signed_at],
                [__('pages.contracts.history_voided'), $contract->voided_at],
            ])->filter(fn (array $entry): bool => $entry[1] !== null)
                ->map(fn (array $entry): array => ['label' => $entry[0], 'at' => $entry[1]->translatedFormat('j M Y, g:i A')])
                ->values(),
            'void_reason' => $contract->void_reason,
            'links' => [
                'public' => $contract->publicUrl(),
                'print' => $contract->publicUrl().'?cetak=1',
                'whatsapp' => 'https://wa.me/'.($phone ?? '').'?text='.rawurlencode($share),
                'edit' => $user->can('update', $contract) ? route('vendor.contracts.edit', $contract) : null,
                'send' => $user->can('send', $contract) ? route('vendor.contracts.send', $contract) : null,
                'void' => $user->can('void', $contract) ? route('vendor.contracts.void', $contract) : null,
                'duplicate' => route('vendor.contracts.duplicate', $contract),
                'destroy' => $user->can('delete', $contract) ? route('vendor.contracts.destroy', $contract) : null,
            ],
            'is_draft' => $contract->status === ContractStatus::Draft,
            'is_sent' => $contract->status === ContractStatus::Sent,
            'has_email' => filled($contract->client_email),
        ];
    }
}
