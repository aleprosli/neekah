<?php

use App\Http\Middleware\BeginPageMetadata;
use App\Http\Middleware\EnsureUserHasRole;
use App\Http\Middleware\EnsureUserHasWedding;
use App\Support\ImageSettings;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(BeginPageMetadata::class);

        $middleware->alias([
            'role' => EnsureUserHasRole::class,
            'wedding' => EnsureUserHasWedding::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

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
                ->withErrors(['image' => 'Fail terlalu besar. Had server ini ialah '.app(ImageSettings::class)->effectiveUploadMegabytes().'MB setiap muat naik.']);
        });
    })->create();
