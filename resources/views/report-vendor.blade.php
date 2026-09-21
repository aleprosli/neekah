<x-layouts.app :title="'Laporkan '.$vendor->name">
    <x-site.header />

    <main class="mx-auto max-w-2xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <nav class="text-sm text-ink-muted" :aria-label="__('pages.report_page.breadcrumb')">
            <a href="{{ route('vendors.show', $vendor) }}" class="hover:text-ink">{{ $vendor->name }}</a> › <span class="text-ink">{{ __('pages.report_page.laporkan_vendor') }}</span>
        </nav>

        <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight">Laporkan {{ $vendor->name }}</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ __('pages.report_page.admin_akan_menyiasat_setiap_laporan') }}</p>

        {{-- resources/js/components/public/ReportVendorForm.vue --}}
        <div data-vue="report-vendor-form" data-props="@vueProps($props)"></div>
    </main>

    <x-site.footer />
</x-layouts.app>
