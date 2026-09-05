<?php

namespace App\Http\Controllers\Customer;

use App\Actions\ImportWeddingGuests;
use App\Enums\GuestGroup;
use App\Enums\GuestSide;
use App\Http\Controllers\Controller;
use App\Http\Requests\ImportWeddingGuestsRequest;
use App\Models\Wedding;
use Illuminate\Http\RedirectResponse;

class WeddingGuestImportController extends Controller
{
    public function store(ImportWeddingGuestsRequest $request, ImportWeddingGuests $import, Wedding $wedding): RedirectResponse
    {
        $result = $import->handle(
            $wedding,
            $request->lines(),
            GuestSide::from($request->string('side')->toString()),
            GuestGroup::from($request->string('group')->toString()),
        );

        return back()
            ->with('status', "{$result['imported']} tetamu ditambah, {$result['updated']} dikemas kini.")
            ->with('importErrors', $result['errors']);
    }
}
