<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\RegisterVendor;
use App\Http\Controllers\Controller;
use App\Http\Requests\ConvertToVendorRequest;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\Seo;
use App\Support\States;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * For a vendor who signed up as a couple by mistake: their email is taken, so
 * the vendor signup form cannot help them.
 */
class AccountConversionController extends Controller
{
    public function create(Request $request, Seo $seo): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->canBecomeVendor()) {
            return $this->refuse($request);
        }

        $seo->title(__('seo.vendor_convert.title'));

        return view('vendor.convert', [
            'props' => VueProps::for([
                'action' => route('vendor.convert'),
                'loginUrl' => route('login'),
                'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
                'states' => States::options(),
                'old' => ['phone' => $user->phone, ...old()],
                'account' => ['name' => $user->name, 'email' => $user->email],
            ]),
        ]);
    }

    public function store(ConvertToVendorRequest $request, RegisterVendor $registerVendor): RedirectResponse
    {
        $registerVendor->convert($request->user(), $request->validated());

        return redirect()
            ->route('vendor.dashboard')
            ->with('status', 'Akaun anda kini akaun vendor. Lengkapkan profil anda sementara admin menyemak permohonan.');
    }

    private function refuse(Request $request): RedirectResponse
    {
        $user = $request->user();

        return redirect($user->homeRoute())->with('status', $user->isCustomer()
            ? 'Akaun ini sudah ada majlis, tempahan atau enquiry, jadi tidak boleh ditukar sendiri. Sila hubungi admin Neekah.'
            : 'Akaun ini bukan akaun pengantin.');
    }
}
