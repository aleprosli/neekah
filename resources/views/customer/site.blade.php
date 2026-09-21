<x-layouts.customer :title="__('pages.dash.kad_jemputan')" :heading="__('pages.dash.kad_jemputan_digital')" :subheading="__('pages.dash.isi_maklumat_majlis_pilih_template')">
    <x-slot:actions>
        <a href="{{ route('sites.templates') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.lihat_semua_template') }}</a>
        <a href="{{ route('site.preview') }}" target="_blank" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.pratonton') }}</a>
    </x-slot:actions>

    <x-invitation-setup :wedding="$wedding" compact class="mb-6" />

    {{-- resources/js/components/customer/CustomerSitePage.vue --}}
    <div data-vue="customer-site-page" data-props="@vueProps($props)"></div>
</x-layouts.customer>
