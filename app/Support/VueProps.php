<?php

namespace App\Support;

use Illuminate\Support\Facades\Session;

/**
 * The props a Blade shell hands to a Vue component.
 *
 * Every page needs the same three things alongside its own data: the CSRF
 * token its forms post with, the first validation error per field, and the
 * input the visitor already typed. Collecting them here keeps that out of
 * every view.
 */
class VueProps
{
    /**
     * @param  array<string, mixed>  $props
     * @return array<string, mixed>
     */
    public static function for(array $props = []): array
    {
        return [
            ...$props,
            'csrf' => csrf_token(),
            'errors' => self::errors(),
        ];
    }

    /**
     * One message per field, which is what a form field can show.
     *
     * @return object|array<string, string>
     */
    public static function errors(): object|array
    {
        $errors = Session::get('errors');

        if (! $errors) {
            return (object) [];
        }

        $messages = collect($errors->getBag('default')->messages())
            ->map(fn (array $messages): string => $messages[0])
            ->all();

        return $messages === [] ? (object) [] : $messages;
    }
}
