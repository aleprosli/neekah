@props(['code', 'title', 'message'])

{{--
    Deliberately standalone: no header, no footer, no settings lookup. An error
    page has to render when the database is down or the session store is gone,
    which is exactly when the ordinary layout would fail too.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="antialiased">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex, nofollow">
        <title>{{ $title }} · {{ config('app.name') }}</title>

        <link rel="icon" type="image/png" href="{{ asset(config('neekah.brand.icon')) }}">
        <meta name="theme-color" content="#7a263a">

        @fonts
        @vite(['resources/css/app.css'])
    </head>
    <body class="min-h-screen bg-surface font-sans text-ink">
        <main class="mx-auto flex min-h-screen max-w-xl flex-col items-center justify-center px-4 py-16 text-center sm:px-6">
            <a href="{{ url('/') }}" aria-label="{{ config('app.name') }}">
                <img src="{{ asset(config('neekah.brand.lockup')) }}" alt="{{ config('app.name') }}" class="h-8 w-auto object-contain">
            </a>

            <p class="mt-10 font-display text-6xl font-semibold tracking-tight text-brand-600 sm:text-7xl">{{ $code }}</p>
            <h1 class="mt-4 font-display text-2xl font-semibold tracking-tight sm:text-3xl">{{ $title }}</h1>
            <p class="mt-3 text-sm text-ink-muted">{{ $message }}</p>

            <div class="mt-8 flex flex-wrap items-center justify-center gap-2">
                <a href="{{ url('/') }}" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.errors.cari_vendor') }}</a>
                <a href="{{ url('/about') }}" class="rounded-full border border-line px-6 py-3 text-sm font-medium transition hover:border-brand-400">{{ __('pages.errors.tentang_neekah') }}</a>
            </div>
        </main>
    </body>
</html>
