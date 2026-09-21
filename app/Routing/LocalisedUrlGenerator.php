<?php

namespace App\Routing;

use App\Support\Locales;
use Illuminate\Routing\UrlGenerator;

/**
 * Sends route() to the version of a route in the language being served.
 *
 * Routes are registered once per language, the non-default ones under a name
 * prefix. Resolving that prefix here rather than at the call sites means the
 * two hundred-odd route('...') calls already written keep working, and a new
 * one does not have to remember which language it is in.
 */
class LocalisedUrlGenerator extends UrlGenerator
{
    /**
     * @param  string  $name
     * @param  mixed  $parameters
     * @param  bool  $absolute
     */
    public function route($name, $parameters = [], $absolute = true)
    {
        $localised = Locales::routeName($name);

        if ($localised !== $name && $this->routes->hasNamedRoute($localised)) {
            $name = $localised;
        }

        return parent::route($name, $parameters, $absolute);
    }

    /**
     * The same route in another language — what the language switcher and the
     * hreflang tags are built from. Null when that language has no such route.
     *
     * @param  array<string, mixed>  $parameters
     */
    public function routeIn(string $locale, string $name, array $parameters = []): ?string
    {
        $target = Locales::routeName($name, $locale);

        if (! $this->routes->hasNamedRoute($target)) {
            return null;
        }

        return parent::route($target, $parameters, true);
    }
}
