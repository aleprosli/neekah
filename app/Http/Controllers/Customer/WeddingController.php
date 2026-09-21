<?php

namespace App\Http\Controllers\Customer;

use App\Actions\SeedWeddingChecklist;
use App\Enums\WeddingRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreWeddingRequest;
use App\Models\Wedding;
use App\Support\States;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class WeddingController extends Controller
{
    public function create(): View
    {
        return view('customer.weddings.form', $this->formData(new Wedding));
    }

    public function store(StoreWeddingRequest $request, SeedWeddingChecklist $seedChecklist): RedirectResponse
    {
        $wedding = $request->user()->createdWeddings()->create($request->validated());
        $wedding->addMember($request->user(), WeddingRole::Owner);
        $seedChecklist->handle($wedding);

        return redirect()->route('dashboard')->with('status', 'Wedding project dicipta, lengkap dengan checklist dan cadangan bajet. Langkah seterusnya: cipta kad kahwin digital anda.');
    }

    public function edit(Wedding $wedding): View
    {
        Gate::authorize('update', $wedding);

        return view('customer.weddings.form', $this->formData($wedding));
    }

    public function update(StoreWeddingRequest $request, Wedding $wedding): RedirectResponse
    {
        $wedding->update($request->validated());

        return redirect()->route('dashboard')->with('status', 'Maklumat majlis dikemas kini.');
    }

    /**
     * What the form component needs, with anything already typed put back.
     *
     * @return array<string, mixed>
     */
    private function formData(Wedding $wedding): array
    {
        $editing = $wedding->exists;

        return [
            'editing' => $editing,
            'props' => VueProps::for([
                'editing' => $editing,
                'action' => $editing ? route('weddings.update', $wedding) : route('weddings.store'),
                'cancelUrl' => route('dashboard'),
                'states' => States::options(),
                'wedding' => [
                    'title' => old('title', $wedding->title),
                    'event_date' => old('event_date', $wedding->event_date?->toDateString()),
                    'budget' => old('budget', $wedding->budget ?? 30000),
                    'city' => old('city', $wedding->city),
                    'state' => old('state', $wedding->state),
                    'notes' => old('notes', $wedding->notes),
                ],
            ]),
        ];
    }
}
