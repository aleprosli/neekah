@props(['title', 'subtitle' => null])

<x-layouts.app :title="$title">
    <x-site.header />

    <main class="mx-auto flex min-h-[80vh] max-w-md flex-col justify-center px-4 pt-24 pb-16 sm:px-6">
        <div class="rounded-3xl border border-line bg-surface-raised p-6 shadow-xl shadow-brand-900/5 sm:p-8">
            <h1 class="font-display text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            @if ($subtitle)
                <p class="mt-1 text-sm text-ink-muted">{{ $subtitle }}</p>
            @endif

            @if ($errors->any())
                <ul class="mt-4 flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs text-brand-800 dark:bg-brand-900/40 dark:text-brand-100">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-6">
                {{ $slot }}
            </div>
        </div>
    </main>

    <x-site.footer />
</x-layouts.app>
