@php
    $notes = __('pages.template_category_notes');
    $groups = $templates->groupBy('category');
    $startUrl = auth()->check() ? route('site.edit') : route('register');
@endphp

<x-layouts.app :title="__('pages.gallery_page.template_kad_jemputan')">
    <x-site.header />

    {{-- The same paper and florals as the About page: the gallery is the
         stationery itself, so it wears what the cards are drawn with. --}}
    <main class="relative overflow-hidden bg-ivory pt-24 pb-24 lg:pt-28">
        <x-site.ornament name="corner-peony" class="absolute -top-16 -left-16 size-[20rem] opacity-50 sm:size-[28rem]" color="var(--color-brand-200)" color2="var(--color-brand-100)" />
        <x-site.ornament name="corner-peony" class="absolute -top-12 -right-20 size-[20rem] rotate-90 opacity-40 sm:size-[28rem]" color="var(--color-gold-300)" color2="var(--color-brand-100)" />

        {{-- Intro --}}
        <section class="relative mx-auto max-w-3xl px-4 text-center sm:px-6">
            <p class="font-script text-3xl text-brand-600 sm:text-4xl">{{ __('pages.gallery_page.kad_kahwin_digital') }}</p>
            <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-balance sm:text-5xl">{{ __('pages.gallery_page.kad_jemputan_yang_terasa_seperti') }}</h1>
            <x-site.ornament name="divider-floral" class="mx-auto mt-5 h-6 w-44" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
            <p class="mx-auto mt-4 max-w-xl text-ink-muted">{{ __('pages.gallery_page.sampul_yang_dibuka_kertas_bertekstur') }}<span class="font-medium text-ink">{{ __('pages.gallery_page.template_untuk_dipilih', ['count' => $templates->count()]) }}</span>{{ __('pages.gallery_page.percuma') }}</p>
            <a href="{{ $startUrl }}" class="mt-7 inline-flex rounded-full bg-brand-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                {{ auth()->check() ? __('pages.gallery_page.cipta_kad_saya') : __('pages.gallery_page.daftar_cipta_kad_percuma') }}
            </a>
        </section>

        {{-- How it works --}}
        <ol class="mx-auto mt-14 grid max-w-4xl gap-3 px-4 sm:grid-cols-3 sm:px-6">
            @foreach ([
                [__('pages.gallery_page.step_pick'), __('pages.gallery_page.step_pick_detail')],
                [__('pages.gallery_page.step_fill'), __('pages.gallery_page.step_fill_detail')],
                [__('pages.gallery_page.step_share'), __('pages.gallery_page.step_share_detail', ['domain' => config('neekah.site_domain')])],
            ] as [$title, $body])
                <li class="flex gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                    <span class="flex size-8 shrink-0 items-center justify-center rounded-full border border-gold-500 bg-surface-raised font-display text-sm font-semibold text-brand-700">{{ $loop->iteration }}</span>
                    <span>
                        <span class="block text-sm font-semibold">{{ $title }}</span>
                        <span class="mt-1 block text-sm break-words text-ink-muted">{{ $body }}</span>
                    </span>
                </li>
            @endforeach
        </ol>

        {{-- Style filter --}}
        <nav class="sticky top-[5.25rem] z-20 mt-12 border-y border-gold-300/60 bg-ivory/90 backdrop-blur" :aria-label="__('pages.gallery_page.gaya')">
            <div class="no-scrollbar mx-auto flex max-w-6xl gap-2 overflow-x-auto px-4 py-3 sm:justify-center sm:px-6">
                <a href="{{ route('sites.templates') }}" @class(['shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition', 'border-brand-600 bg-brand-600 text-white' => ! $category, 'border-line hover:border-brand-400' => $category])>{{ __('pages.gallery_page.semua') }}</a>
                @foreach ($categories as $name)
                    <a href="{{ route('sites.templates', ['category' => $name]) }}" @class(['shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition', 'border-brand-600 bg-brand-600 text-white' => $category === $name, 'border-line hover:border-brand-400' => $category !== $name])>{{ __('pages.template_category.'.$name) }}</a>
                @endforeach
            </div>
        </nav>

        {{-- Designs, one shelf per style --}}
        <div class="mx-auto flex max-w-6xl flex-col gap-16 px-4 pt-12 sm:px-6 lg:px-10">
            @foreach ($groups as $name => $group)
                <section>
                    <div class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-1 border-b border-gold-300 pb-3">
                        <h2 class="font-display text-2xl font-semibold">{{ __('pages.template_category.'.$name) }}</h2>
                        <p class="text-sm text-ink-muted">{{ $notes[$name] ?? '' }} <span class="whitespace-nowrap">· {{ __('pages.gallery_page.designs', ['count' => $group->count()]) }}</span></p>
                    </div>

                    <ul class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($group as $template)
                            <li>
                                <a href="{{ route('sites.templates.show', $template) }}" class="group block">
                                    <div class="overflow-hidden rounded-md bg-surface-muted p-2.5 transition group-hover:-translate-y-1 sm:p-3.5">
                                        {{-- The tile is the design itself, drawn by
                                             resources/js/components/card/CardView.vue at
                                             thumbnail size. Without JavaScript the name
                                             and the palette still describe it. --}}
                                        <div class="aspect-[9/16] overflow-hidden shadow-[0_12px_28px_-12px_rgb(0_0_0/0.35)] transition group-hover:shadow-[0_20px_36px_-14px_rgb(0_0_0/0.4)]" data-vue="card-view" data-props="@vueProps($thumbnails[$template->slug])">
                                            <div class="flex h-full w-full flex-col items-center justify-center gap-2 p-3 text-center" style="{{ $template->cssVariables() }};background:var(--c-bg);color:var(--c-onbg)">
                                                <span class="text-[0.6rem] tracking-[0.3em] uppercase" style="color:var(--c-acc)">Walimatul Urus</span>
                                                <span class="text-sm" style="font-family:var(--f-s);color:var(--c-head)">{{ $template->name }}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <span class="mt-3 flex items-center justify-between gap-2">
                                        <span class="truncate text-sm font-semibold group-hover:text-brand-700">{{ $template->name }}</span>
                                        <span class="shrink-0 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">{{ __('pages.gallery_page.lihat') }}</span>
                                    </span>
                                    <span class="mt-0.5 line-clamp-2 block text-xs text-ink-muted">{{ $template->description }}</span>
                                    @if ($template->is_premium)
                                        <span class="mt-1 inline-flex rounded-full bg-brand-50 px-2 py-0.5 text-[0.65rem] font-semibold text-brand-700">{{ __('pages.gallery_page.premium') }}</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>

        <div class="mx-auto mt-16 max-w-2xl px-4 sm:px-6">
            <div class="relative overflow-hidden rounded-3xl border border-gold-300 bg-surface-raised px-6 py-12 text-center">
                <span class="pointer-events-none absolute inset-3 rounded-[1.25rem] border border-gold-300/70" aria-hidden="true"></span>
                <x-site.ornament name="corner-rose" class="absolute -top-8 -left-8 size-36 opacity-50" color="var(--color-brand-300)" color2="var(--color-brand-100)" />
                <x-site.ornament name="corner-rose" class="absolute -right-8 -bottom-8 size-36 rotate-180 opacity-50" color="var(--color-brand-300)" color2="var(--color-gold-300)" />
                <div class="relative">
                    <p class="font-display text-xl font-semibold">{{ __('pages.gallery_page.sudah_jumpa_yang_berkenan') }}</p>
                    <p class="mt-2 text-sm text-ink-muted">{{ __('pages.gallery_page.anda_boleh_tukar_template_bila') }}</p>
                    <a href="{{ $startUrl }}" class="mt-5 inline-flex rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">
                        {{ auth()->check() ? __('pages.gallery_page.cipta_kad_jemputan_saya') : __('pages.gallery_page.daftar_untuk_mula') }}
                    </a>
                </div>
            </div>
        </div>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
