<?php

use App\Http\Middleware\BeginPageMetadata;
use App\Http\Middleware\EnsureAccountIsActive;
use App\Http\Middleware\EnsurePhoneNumber;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserHasWedding;
use App\Http\Middleware\EnsureVendorIsApproved;
use App\Http\Middleware\SetLocale;
use App\Support\ImageSettings;
use App\Support\Locales;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(BeginPageMetadata::class);
        $middleware->appendToGroup('web', EnsureAccountIsActive::class);
        $middleware->appendToGroup('web', EnsurePhoneNumber::class);

        $middleware->validateCsrfTokens(except: ['webhooks/*']);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'wedding' => EnsureUserHasWedding::class,
            'vendor.approved' => EnsureVendorIsApproved::class,
            'locale' => SetLocale::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        /**
         * A URL that matches no route never reaches the locale middleware, so
         * a 404 under /en was answered in Malay. Returning null lets Laravel
         * render the page as it normally would, now in the right language.
         */
        $exceptions->render(function (Throwable $exception, Request $request) {
            App::setLocale(Locales::fromPath($request->path()));

            return null;
        });

        /**
         * A POST larger than post_max_size is dropped by PHP before the form
         * ever reaches a controller, which otherwise surfaces as a blank
         * "page expired". Say what actually happened instead.
         */
        $exceptions->render(function (PostTooLargeException $exception, Request $request) {
            if ($request->expectsJson()) {
                return null;
            }

            return back()
                ->withInput($request->except('_token'))
                ->withErrors(['image' => __('validation.custom.file_too_large', [
                    'size' => app(ImageSettings::class)->effectiveUploadMegabytes(),
                ])]);
        });
    })->create();
