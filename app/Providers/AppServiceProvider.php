<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RedirectIfAuthenticated::redirectUsing(
            fn (Request $request) => $request->user()->homeRoute(),
        );

        // Only admins may read the application log.
        Gate::define('viewLogViewer', fn (?User $user) => $user?->isAdmin() ?? false);
    }
}
