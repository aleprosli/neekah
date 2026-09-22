<x-layouts.customer :title="__('pages.dash.statistik_kad')" :heading="__('pages.dash.statistik_kad_jemputan')" :subheading="__('pages.dash.siapa_membuka_kad_anda')">
    <x-slot:actions>
        <a href="{{ route('site.edit') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.kembali_ke_editor') }}</a>
    </x-slot:actions>

    {{-- resources/js/components/customer/CustomerCardInsights.vue --}}
    <div data-vue="customer-card-insights" data-props="@vueProps($props)"></div>
</x-layouts.customer>
