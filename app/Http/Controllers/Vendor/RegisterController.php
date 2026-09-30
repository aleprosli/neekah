<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\RegisterVendor;
use App\Enums\AuthAudience;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterVendorRequest;
use App\Models\Category;
use App\Support\AuthForm;
use App\Support\Seo;
use App\Support\States;
use App\Support\TurnstileSettings;
use App\Support\VueProps;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create(Seo $seo): View
    {
        $seo->title(__('seo.vendor_register.title'))
            ->description(__('seo.vendor_register.description'));

        $turnstile = app(TurnstileSettings::class);

        return view('vendor.register', [
            'props' => VueProps::for([
                'action' => route('vendor.register'),
                'loginUrl' => AuthAudience::Vendor->loginUrl(),
                'convertUrl' => route('vendor.convert'),
                'categories' => Category::active()->ordered()->get(['id', 'name', 'icon']),
                'states' => States::options(),
                'districts' => States::districtOptions(),
                'old' => old(),
                'turnstileSiteKey' => $turnstile->isEnabled() ? $turnstile->siteKey() : null,
                'accessCode' => AuthForm::accessCodeFields()[0] ?? null,
            ]),
        ]);
    }

    public function store(RegisterVendorRequest $request, RegisterVendor $registerVendor): RedirectResponse
    {
        $vendor = $registerVendor->handle($request->validated());

        Auth::login($vendor->user);
        $request->session()->regenerate();

        return redirect()
            ->route('vendor.dashboard')
            ->with('status', __('flash.vendor.registered'));
    }
}
