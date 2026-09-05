@props(['title', 'nav', 'heading' => null, 'subheading' => null])

@php app(App\Support\Seo::class)->noindex(); @endphp

<x-layouts.app :title="$title">
    <x-site.header />

    <div class="mx-auto max-w-7xl px-4 pt-24 pb-24 sm:px-6 lg:px-10 lg:pt-28 md:pb-12">
        <div class="grid gap-8 lg:grid-cols-[220px_1fr]">
            {{-- min-w-0 stops the nav's scrollable row from stretching the grid column past the viewport on a phone. --}}
            <aside class="min-w-0 lg:sticky lg:top-28 lg:self-start">
                <nav class="no-scrollbar -mx-4 flex gap-1 overflow-x-auto px-4 lg:mx-0 lg:flex-col lg:px-0" aria-label="Dashboard">
                    @foreach ($nav as $item)
                        <a href="{{ $item['href'] }}" @class(['flex shrink-0 items-center gap-2 rounded-full px-4 py-2 text-sm font-medium whitespace-nowrap transition lg:rounded-xl', 'bg-brand-600 text-white' => $item['active'], 'text-ink-muted hover:bg-surface-muted hover:text-ink' => ! $item['active']])>
                            <span aria-hidden="true">{{ $item['icon'] }}</span>
                            {{ $item['label'] }}
                            @if (! empty($item['badge']))
                                <span @class(['ml-auto rounded-full px-1.5 text-[11px] font-semibold', 'bg-white/20 text-white' => $item['active'], 'bg-brand-600 text-white' => ! $item['active']])>{{ $item['badge'] }}</span>
                            @endif
                        </a>
                    @endforeach
                </nav>
            </aside>

            <main class="min-w-0">
                @if ($heading)
                    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                        <div>
                            <h1 class="font-display text-3xl font-semibold tracking-tight">{{ $heading }}</h1>
                            @if ($subheading)
                                <p class="mt-1 text-sm text-ink-muted">{{ $subheading }}</p>
                            @endif
                        </div>
                        @isset($actions)
                            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
                        @endisset
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <ul class="mb-6 flex flex-col gap-1 rounded-2xl bg-brand-50 p-4 text-sm text-brand-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
