<x-layouts.admin :title="__('pages.dash.analitik')" :heading="__('pages.dash.analitik_platform')" :subheading="__('pages.dash.tempoh_terakhir', ['period' => $period->label()])">
    <x-slot:actions>
        <x-filter-popover :label="__('pages.dash.tempoh')" :active="$period->label()" align="right" width="w-48">
            <ul class="flex flex-col gap-1">
                @foreach (\App\Support\AnalyticsPeriod::CHOICES as $months => $label)
                    <li><a href="{{ route('admin.analytics', ['months' => $months]) }}" @class(['block rounded-xl px-3 py-2 text-sm', 'bg-brand-50 font-semibold text-brand-700' => $period->months === $months, 'hover:bg-surface-muted' => $period->months !== $months])>{{ $label }}</a></li>
                @endforeach
            </ul>
        </x-filter-popover>
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminAnalyticsPage.vue --}}
    <div data-vue="admin-analytics-page" data-props="@vueProps($props)"></div>
</x-layouts.admin>
