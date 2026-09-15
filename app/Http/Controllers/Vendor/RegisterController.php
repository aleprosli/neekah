<?php

namespace App\Http\Controllers\Vendor;

use App\Actions\RegisterVendor;
use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterVendorRequest;
use App\Models\Category;
use App\Models\Vendor;
use App\Support\Seo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function create(Seo $seo): View
    {
        $seo->title('Daftar sebagai vendor perkahwinan')
            ->description('Sertai Neekah dan terima tempahan daripada pasangan di seluruh Malaysia. Profil percuma, bayaran direkod dalam platform, ranking ikut prestasi sebenar.');

        return view('vendor.register', [
            'categories' => Category::active()->ordered()->get(),
            'states' => Vendor::STATES,
        ]);
    }

    public function store(RegisterVendorRequest $request, RegisterVendor $registerVendor): RedirectResponse
    {
        $vendor = $registerVendor->handle($request->validated());

        Auth::login($vendor->user);
        $request->session()->regenerate();

        return redirect()
            ->route('vendor.dashboard')
            ->with('status', 'Pendaftaran diterima. Lengkapkan profil anda sementara admin menyemak permohonan.');
    }
}
