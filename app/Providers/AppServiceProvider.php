<?php

namespace App\Providers;

use App\Models\User;
use App\Routing\LocalisedUrlGenerator;
use App\Support\Herepay\DepositGateway;
use App\Support\Herepay\HerepayClient;
use App\Support\Herepay\HerepayDepositClient;
use App\Support\Herepay\PaymentLinkGateway;
use App\Support\Seo;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\UrlGenerator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // One description of the current page, shared by the layout and by
        // whichever controller knows what the page actually is.
        $this->app->scoped(Seo::class);
        $this->app->bind(PaymentLinkGateway::class, HerepayClient::class);
        $this->app->bind(DepositGateway::class, HerepayDepositClient::class);

        // route() has to answer with the page in the language being served.
        $this->app->extend('url', function (UrlGenerator $url, $app): LocalisedUrlGenerator {
            $localised = new LocalisedUrlGenerator($app['router']->getRoutes(), $app['request'], $app['config']->get('app.asset_url'));

            $localised->setSessionResolver(fn () => $app['session'] ?? null);
            $localised->setKeyResolver(fn () => $app->make('config')->get('app.key'));

            $app->rebinding('request', fn ($app, $request) => $localised->setRequest($request));

            return $localised;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Every Vue island hands its props over the same way.
        Blade::directive('vueProps', fn (string $expression): string => "<?php echo e(App\\Support\\VueProps::encode({$expression})); ?>");

        RedirectIfAuthenticated::redirectUsing(
            fn (Request $request) => $request->user()->homeRoute(),
        );

        // One pager for every list: a window of pages, not all of them. See
        // resources/views/pagination/neekah.blade.php.
        Paginator::defaultView('pagination.neekah');

        // Only admins may read the application log.
        Gate::define('viewLogViewer', fn (?User $user) => $user?->isAdmin() ?? false);
    }
}
