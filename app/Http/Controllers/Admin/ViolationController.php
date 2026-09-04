<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ApplyViolationAction;
use App\Enums\ViolationAction;
use App\Enums\ViolationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\ResolveViolationRequest;
use App\Models\VendorViolation;
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

        return view('admin.violations.index', [
            'violations' => $violations,
            'status' => $status,
            'counts' => VendorViolation::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
        ]);
    }

    public function show(VendorViolation $violation): View
    {
        $violation->load(['vendor.user', 'reporter', 'booking', 'resolver']);

        return view('admin.violations.show', [
            'violation' => $violation,
            'history' => $violation->vendor->violations()->upheld()->whereKeyNot($violation->getKey())->latest()->get(),
            'nextAction' => ViolationAction::forOffence($violation->vendor->violations()->upheld()->count() + 1),
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
