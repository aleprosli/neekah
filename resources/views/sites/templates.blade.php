<x-layouts.app title="Template kad jemputan">
    <x-site.header />

    <main class="mx-auto max-w-5xl px-4 pt-24 pb-24 sm:px-6 lg:px-10 lg:pt-28">
        <div class="text-center">
            <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Kad jemputan digital</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight sm:text-4xl">Pilih template anda</h1>
            <p class="mx-auto mt-3 max-w-xl text-ink-muted">Setiap kad datang dengan alamat web sendiri, atur cara majlis, peta lokasi dan RSVP. Tekan mana-mana template untuk melihat contoh penuh sebelum anda mula.</p>
        </div>

        <ul class="mt-10 grid gap-6 sm:grid-cols-2">
            @foreach ($templates as $slug => $template)
                <li>
                    <a href="{{ route('sites.templates.show', $slug) }}" class="group flex flex-col gap-3">
                        <span class="relative flex aspect-[4/3] items-center justify-center overflow-hidden rounded-3xl bg-linear-to-br text-center transition group-hover:shadow-xl group-hover:shadow-brand-900/10 {{ $template['palette'] }}">
                            <span class="px-8 text-white">
                                <span class="block font-display text-2xl font-semibold sm:text-3xl">Aina &amp; Hakim</span>
                                <span class="mx-auto my-3 block h-px w-12 bg-white/50"></span>
                                <span class="block text-xs tracking-[0.3em] uppercase">{{ $template['name'] }}</span>
                            </span>
                        </span>
                        <span>
                            <span class="block font-semibold group-hover:text-brand-700">{{ $template['name'] }}</span>
                            <span class="block text-sm text-ink-muted">{{ $template['description'] }}</span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="mt-10 text-center">
            <a href="{{ auth()->check() ? route('site.edit') : route('register') }}" class="inline-flex rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                {{ auth()->check() ? 'Cipta kad jemputan saya' : 'Daftar untuk mula' }}
            </a>
        </div>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
