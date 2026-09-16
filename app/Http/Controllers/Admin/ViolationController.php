<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApplyViolationAction;
use App\Enums\ViolationAction;
use App\Enums\ViolationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveViolationRequest;
use App\Models\VendorViolation;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ViolationController extends Controller
{
    public function index(Request $request): View
    {
        $status = ViolationStatus::tryFrom($request->string('status')->toString());

        $violations = VendorViolation::query()
            ->with(['vendor', 'reporter', 'booking'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByRaw("case when status = 'open' then 0 else 1 end")
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $violations->setCollection($violations->getCollection()->map(fn (VendorViolation $violation): array => [
            'id' => $violation->id,
            'url' => route('admin.violations.show', $violation),
            'vendor' => $violation->vendor->name,
            'is_open' => $violation->isOpen(),
            'badge' => $violation->isOpen()
                ? 'Perlu semakan'
                : ($violation->action?->label() ?? $violation->status->label()),
            'type' => $violation->type->label(),
            'reporter' => $violation->reporter?->name ?? 'pengguna dipadam',
            'description' => $violation->description,
            'reported' => $violation->created_at->diffForHumans(),
        ]));

        return view('admin.violations.index', [
            'violations' => $violations,
            'status' => $status,
            'counts' => VendorViolation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
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
                        ['label' => 'Status vendor', 'value' => $violation->vendor->status->label()],
                        ['label' => 'Tahap', 'value' => $violation->vendor->tier->label()],
                        ['label' => 'Penalty points', 'value' => $violation->vendor->penalty_points],
                        ['label' => 'Vendor Score', 'value' => number_format((float) $violation->vendor->score, 2)],
                    ],
                ],
            ]),
        ]);
    }

    public function update(ResolveViolationRequest $request, VendorViolation $violation, ApplyViolationAction $applyAction): RedirectResponse
    {
        if (! $violation->isOpen()) {
            return back()->withErrors(['decision' => 'Laporan ini telah diselesaikan.']);
        }

        $note = $request->string('admin_note')->toString() ?: null;

        if ($request->string('decision')->toString() === 'uphold') {
            $applyAction->uphold($violation, $request->user(), $note);

            return back()->with('status', 'Laporan disahkan. Tindakan '.$violation->fresh()->action->label().' telah dikenakan.');
        }

        $applyAction->dismiss($violation, $request->user(), $note);

        return back()->with('status', 'Laporan ditolak. Tiada tindakan dikenakan ke atas vendor.');
    }
}
