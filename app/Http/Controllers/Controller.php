<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Finish an action that may have been posted by the upload progress bar.
     *
     * XHR follows a 302 invisibly, which would consume the flash message meant
     * for the page the visitor lands on. So an XHR gets the destination as JSON
     * and navigates there itself, with the message still waiting in the session.
     */
    protected function redirectOrJson(Request $request, string $url, ?string $status = null): RedirectResponse|JsonResponse
    {
        if ($status !== null) {
            $request->session()->flash('status', $status);
        }

        return $request->expectsJson()
            ? response()->json(['redirect' => $url])
            : redirect()->to($url);
    }

    /**
     * Where a vendor lands after changing their catalogue. One still waiting
     * for approval works only from the setup cards on the dashboard, so the
     * page they came from is the dashboard, not the full editor.
     */
    protected function vendorReturnUrl(Request $request, string $route, string $section): string
    {
        return $request->user()->vendor->isAwaitingApproval()
            ? route('vendor.dashboard').'#'.$section
            : route($route);
    }
}
