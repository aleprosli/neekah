<x-layouts.app title="Template kad jemputan">
    <x-site.header />

    <main class="mx-auto max-w-6xl px-4 pt-24 pb-24 sm:px-6 lg:px-10 lg:pt-28">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Kad jemputan digital</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">{{ $templates->count() }} template untuk dipilih</h1>
            <p class="mx-auto mt-3 max-w-xl text-ink-muted">Setiap kad datang dengan alamat web sendiri, animasi pembuka, kiraan detik, atur cara, peta dan RSVP. Tekan mana-mana reka bentuk untuk melihat contoh penuh.</p>
        </div>

        <div class="mt-8 flex flex-wrap justify-center gap-2">
            <a href="{{ route('sites.templates') }}" @class(['rounded-full border px-4 py-2 text-sm font-medium transition', 'border-brand-600 bg-brand-600 text-white' => ! $style, 'border-line hover:border-brand-400' => $style])>Semua</a>
            @foreach ($styles as $name)
                <a href="{{ route('sites.templates', ['style' => $name]) }}" @class(['rounded-full border px-4 py-2 text-sm font-medium transition', 'border-brand-600 bg-brand-600 text-white' => $style === $name, 'border-line hover:border-brand-400' => $style !== $name])>{{ $name }}</a>
            @endforeach
        </div>

        <ul class="mt-10 grid grid-cols-2 gap-5 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($templates as $template)
                <li>
                    <a href="{{ route('sites.templates.show', $template) }}" class="group flex flex-col gap-2.5">
                        <span class="block overflow-hidden rounded-2xl border border-line transition group-hover:shadow-xl group-hover:shadow-brand-900/10">
                            @include('sites.partials.thumbnail', ['template' => $template])
                        </span>
                        <span>
                            <span class="block text-sm font-semibold group-hover:text-brand-700">{{ $template->name }}</span>
                            <span class="block text-xs text-ink-muted">{{ $template->style }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-12 text-center">
            <a href="{{ auth()->check() ? route('site.edit') : route('register') }}" class="inline-flex rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                {{ auth()->check() ? 'Cipta kad jemputan saya' : 'Daftar untuk mula' }}
            </a>
        </div>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
