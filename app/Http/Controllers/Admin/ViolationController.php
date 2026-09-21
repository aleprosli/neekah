<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApplyViolationAction;
use App\Enums\ViolationAction;
use App\Enums\ViolationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveViolationRequest;
use App\Models\VendorViolation;
use App\Support\TableFilter;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ViolationController extends Controller
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
            ['key' => 'vendor', 'label' => __('props.admin.vendor_6'), 'type' => 'html'],
            ['key' => 'type', 'label' => __('props.admin.jenis')],
            ['key' => 'reporter', 'label' => __('props.admin.dilaporkan_oleh')],
            ['key' => 'description', 'label' => __('props.admin.aduan')],
            ['key' => 'reported', 'label' => __('props.admin.bila')],
            ['key' => 'badge', 'label' => __('props.admin.status_7'), 'type' => 'html'],
        ];
    }

    public function index(Request $request): View
    {
        return view('admin.violations.index', [
            'columns' => self::columns(),
            'filters' => [TableFilter::fromEnum(
                'status',
                ViolationStatus::cases(),
                ViolationStatus::tryFrom($request->string('status')->toString())?->value,
                VendorViolation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            )],
        ]);
    }

    /**
     * A page of reports, the ones still to be looked at first.
     */
    public function data(Request $request): JsonResponse
    {
        $status = ViolationStatus::tryFrom($request->string('status')->toString());

        $matching = VendorViolation::query()
            ->when($request->string('search')->trim()->toString(), function ($query, string $keyword): void {
                $like = '%'.$keyword.'%';
                $query->where(fn ($query) => $query->where('description', 'like', $like)
                    ->orWhereHas('vendor', fn ($vendor) => $vendor->where('name', 'like', $like)));
            });

        $violations = $matching->clone()
            ->with(['vendor', 'reporter', 'booking'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(min($request->integer('per_page', 20), 100));

        return response()->json([
            'data' => $violations->getCollection()->map(fn (VendorViolation $violation): array => [
                'url' => route('admin.violations.show', $violation),
                'vendor' => e($violation->vendor->name),
                'type' => $violation->type->label(),
                'reporter' => $violation->reporter?->name ?? 'pengguna dipadam',
                'description' => $violation->description,
                'reported' => $violation->created_at->diffForHumans(),
                'badge' => view('components.admin.status-pill', [
                    'label' => $violation->isOpen() ? 'Perlu semakan' : ($violation->action?->label() ?? $violation->status->label()),
                    'tone' => $violation->isOpen() ? 'amber' : 'muted',
                ])->render(),
            ])->all(),
            'filters' => ['status' => TableFilter::countsByColumn($matching, 'status')],
            'meta' => [
                'total' => $violations->total(),
                'per_page' => $violations->perPage(),
                'current_page' => $violations->currentPage(),
                'last_page' => $violations->lastPage(),
            ],
        ]);
    }

    public function show(VendorViolation $violation): View
    {
        $violation->load(['vendor.user', 'reporter', 'booking', 'resolver']);

        $upheld = $violation->vendor->violations()->upheld()->count();

        return view('admin.violations.show', [
            'violation' => $violation,
            'props' => VueProps::for([
                'action' => ViolationAction::forOffence($upheld + 1)->label(),
                'violation' => [
                    'description' => $violation->description,
                    'is_open' => $violation->isOpen(),
                    'status' => $violation->status->label(),
                    'action' => $violation->action?->label(),
                    'admin_note' => $violation->admin_note,
                    'offence_number' => $violation->offence_number,
                    'next_offence_number' => $upheld + 1,
                    'resolver' => $violation->resolver?->name,
                    'resolved_at' => $violation->resolved_at?->translatedFormat('j M Y, g:i A'),
                    'update_url' => route('admin.violations.update', $violation),
                    'reporter' => [
                        'name' => $violation->reporter?->name ?? 'Pengguna dipadam',
                        'email' => $violation->reporter?->email,
                    ],
                    'booking' => $violation->booking ? [
                        'reference' => $violation->booking->reference,
                        'url' => route('admin.bookings.show', $violation->booking),
                    ] : null,
                ],
                'history' => $violation->vendor->violations()->upheld()->whereKeyNot($violation->getKey())->latest()->get()
                    ->map(fn (VendorViolation $past): array => [
                        'id' => $past->id,
                        'offence_number' => $past->offence_number,
                        'type' => $past->type->label(),
                        'action' => $past->action?->label(),
                        'resolved_at' => $past->resolved_at?->translatedFormat('j M Y'),
                    ])->values(),
                'vendor' => [
                    'summary' => [
                        ['label' => __('props.admin.status_vendor'), 'value' => $violation->vendor->status->label()],
                        ['label' => __('props.admin.tahap_2'), 'value' => $violation->vendor->tier->label()],
                        ['label' => __('props.admin.penalty_points'), 'value' => $violation->vendor->penalty_points],
                        ['label' => __('props.admin.vendor_score_2'), 'value' => number_format((float) $violation->vendor->score, 2)],
                    ],
                ],
            ]),
        ]);
    }

    public function update(ResolveViolationRequest $request, VendorViolation $violation, ApplyViolationAction $applyAction): RedirectResponse
    {
        if (! $violation->isOpen()) {
            return back()->withErrors(['decision' => __('flash.admin.violation_resolved')]);
        }

        $note = $request->string('admin_note')->toString() ?: null;

        if ($request->string('decision')->toString() === 'uphold') {
            $applyAction->uphold($violation, $request->user(), $note);

            return back()->with('status', __('flash.admin.violation_upheld', ['action' => $violation->fresh()->action->label()]));
        }

        $applyAction->dismiss($violation, $request->user(), $note);

        return back()->with('status', __('flash.admin.violation_rejected'));
    }
}
