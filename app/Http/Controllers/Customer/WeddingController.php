<?php

namespace App\Http\Controllers\Customer;

use App\Actions\SeedWeddingChecklist;
use App\Enums\WeddingRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingRequest;
use App\Models\Vendor;
use App\Models\Wedding;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WeddingController extends Controller
{
    public function create(): View
    {
        return view('customer.weddings.form', ['wedding' => new Wedding, 'states' => Vendor::STATES]);
    }

    public function store(StoreWeddingRequest $request, SeedWeddingChecklist $seedChecklist): RedirectResponse
    {
        $wedding = $request->user()->createdWeddings()->create($request->validated());
        $wedding->addMember($request->user(), WeddingRole::Owner);
        $seedChecklist->handle($wedding);

        return redirect()->route('dashboard')->with('status', 'Wedding project dicipta, lengkap dengan checklist dan cadangan bajet.');
    }

    public function edit(Wedding $wedding): View
    {
        Gate::authorize('update', $wedding);

        return view('customer.weddings.form', ['wedding' => $wedding, 'states' => Vendor::STATES]);
    }

    public function update(StoreWeddingRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->update($request->validated());

        return redirect()->route('dashboard')->with('status', 'Maklumat majlis dikemas kini.');
    }
}
