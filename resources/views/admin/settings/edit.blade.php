{{-- One settings page at a time. The menu is plain links, so every page has
     its own address, a save comes back to the same page, and navigation.js
     swaps just the page. On a laptop it is a column grouped by subject; on a
     phone the same links run as one row of chips. --}}
<x-layouts.admin :title="$current['label'].' · '.__('pages.dash.tetapan')" :heading="__('pages.dash.tetapan_2')" :subheading="__('pages.dash.maklumat_perhubungan_seo_keselamatan_borang')">
    <div class="flex min-w-0 flex-col gap-6 lg:grid lg:grid-cols-[230px_minmax(0,1fr)] lg:items-start lg:gap-8">
        <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:px-0 lg:sticky lg:top-24 lg:flex-col lg:gap-5 lg:overflow-visible" aria-label="{{ __('pages.dash.tetapan') }}">
            @foreach ($menu as $group)
                <div class="flex shrink-0 gap-2 lg:flex-col lg:gap-1">
                    <p class="hidden px-3 font-display text-[13px] text-gold-600 italic lg:block">{{ $group['label'] }}</p>
                    @foreach ($group['items'] as $item)
                        <a href="{{ $item['href'] }}" @class([
                            'flex shrink-0 items-center gap-2 rounded-full border px-4 py-2 text-sm font-medium whitespace-nowrap transition lg:rounded-xl lg:border-transparent lg:px-3',
                            'border-brand-300 bg-brand-50 text-brand-800 lg:border-brand-100' => $item['active'],
                            'border-line text-ink-muted hover:border-brand-300 hover:text-ink lg:hover:bg-surface-raised' => ! $item['active'],
                        ]) @if ($item['active']) aria-current="page" @endif>
                            <span aria-hidden="true">{{ $item['icon'] }}</span>
                            <span class="lg:flex-1">{{ $item['label'] }}</span>
                            @if ($item['badge'])
                                <span @class([
                                    'size-2 shrink-0 rounded-full',
                                    'bg-emerald-500' => $item['badge']['active'],
                                    'bg-line' => ! $item['badge']['active'],
                                ]) title="{{ $item['badge']['label'] }}"></span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endforeach
        </nav>

        {{-- resources/js/components/admin/AdminSettingsPage.vue --}}
        <div class="min-w-0" data-vue="admin-settings-page" data-props="@vueProps($props)"></div>
    </div>
</x-layouts.admin>
