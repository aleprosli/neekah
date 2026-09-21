@php
    $notes = [
        'Klasik' => 'Kertas krim, dakwat marun dan emas — gaya kad cetak tradisional.',
        'Islamik' => 'Dibuka dengan Bismillah, bercorak geometri dan gerbang mihrab.',
        'Bunga' => 'Warna pastel dengan kelopak yang gugur perlahan.',
        'Moden' => 'Tipografi bersih, ruang lapang dan tona tanah.',
        'Malam' => 'Latar gelap dan kilauan emas untuk resepsi malam.',
    ];
    $groups = $templates->groupBy('style');
    $startUrl = auth()->check() ? route('site.edit') : route('register');
@endphp

<x-layouts.app title="Template kad jemputan">
    <x-site.header />

    <main class="pt-24 pb-24 lg:pt-28">
        {{-- Intro --}}
        <section class="mx-auto max-w-3xl px-4 text-center sm:px-6">
            <p class="text-sm font-semibold tracking-wide text-brand-600 uppercase">Kad kahwin digital</p>
            <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-balance sm:text-5xl">Kad jemputan yang terasa seperti kad sebenar</h1>
            <p class="mx-auto mt-4 max-w-xl text-ink-muted">Sampul yang dibuka, kertas bertekstur dan nama anda dalam tulisan khat. Tetamu dapat peta, kalendar dan RSVP dalam satu pautan &mdash; <span class="font-medium text-ink">{{ $templates->count() }} template untuk dipilih</span>, percuma.</p>
            <a href="{{ $startUrl }}" class="mt-7 inline-flex rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                {{ auth()->check() ? 'Cipta kad saya' : 'Daftar & cipta kad percuma' }}
            </a>
        </section>

        {{-- How it works --}}
        <ol class="mx-auto mt-14 grid max-w-4xl gap-3 px-4 sm:grid-cols-3 sm:px-6">
            @foreach ([
                ['Pilih template', 'Tekan mana-mana reka bentuk di bawah untuk melihat contoh penuh.'],
                ['Isi maklumat majlis', 'Nama, tarikh, tempat dan atur cara. Kami isi draf daripada majlis anda.'],
                ['Pilih alamat & kongsi', 'Contohnya aina-hakim.'.config('neekah.site_domain').' — hantar di WhatsApp.'],
            ] as [$title, $body])
                <li class="flex gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-brand-50 font-display text-sm font-semibold text-brand-700">{{ $loop->iteration }}</span>
                    <span>
                        <span class="block text-sm font-semibold">{{ $title }}</span>
                        <span class="mt-1 block text-sm break-words text-ink-muted">{{ $body }}</span>
                    </span>
                </li>
            @endforeach
        </ol>

        {{-- Style filter --}}
        <nav class="sticky top-[5.25rem] z-20 mt-12 border-y border-line bg-surface/90 backdrop-blur" aria-label="Gaya">
            <div class="no-scrollbar mx-auto flex max-w-6xl gap-2 overflow-x-auto px-4 py-3 sm:justify-center sm:px-6">
                <a href="{{ route('sites.templates') }}" @class(['shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition', 'border-brand-600 bg-brand-600 text-white' => ! $style, 'border-line hover:border-brand-400' => $style])>Semua</a>
                @foreach ($styles as $name)
                    <a href="{{ route('sites.templates', ['style' => $name]) }}" @class(['shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition', 'border-brand-600 bg-brand-600 text-white' => $style === $name, 'border-line hover:border-brand-400' => $style !== $name])>{{ $name }}</a>
                @endforeach
            </div>
        </nav>

        {{-- Designs, one shelf per style --}}
        <div class="mx-auto flex max-w-6xl flex-col gap-16 px-4 pt-12 sm:px-6 lg:px-10">
            @foreach ($groups as $name => $group)
                <section>
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-line pb-3">
                        <h2 class="font-display text-2xl font-semibold">{{ $name }}</h2>
                        <p class="text-sm text-ink-muted">{{ $notes[$name] ?? '' }} <span class="whitespace-nowrap">· {{ $group->count() }} reka bentuk</span></p>
                    </div>

                    <ul class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($group as $template)
                            <li>
                                <a href="{{ route('sites.templates.show', $template) }}" class="group block">
                                    <span class="block overflow-hidden rounded-md bg-surface-muted p-2.5 transition group-hover:-translate-y-1 sm:p-3.5">
                                        <span class="block shadow-[0_12px_28px_-12px_rgb(0_0_0/0.35)] transition group-hover:shadow-[0_20px_36px_-14px_rgb(0_0_0/0.4)]">
                                            @include('sites.partials.thumbnail', ['template' => $template->toCardDesign()])
                                        </span>
                                    </span>
                                    <span class="mt-3 flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold group-hover:text-brand-700">{{ $template->name }}</span>
                                        <span class="shrink-0 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">Lihat &rarr;</span>
                                    </span>
                                    <span class="mt-0.5 line-clamp-2 block text-xs text-ink-muted">{{ $template->description }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <div class="mx-auto mt-16 max-w-xl px-4 text-center">
            <p class="font-display text-xl font-semibold">Sudah jumpa yang berkenan?</p>
            <p class="mt-2 text-sm text-ink-muted">Anda boleh tukar template bila-bila masa; maklumat majlis kekal.</p>
            <a href="{{ $startUrl }}" class="mt-5 inline-flex rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                {{ auth()->check() ? 'Cipta kad jemputan saya' : 'Daftar untuk mula' }}
            </a>
        </div>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
