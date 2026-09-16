<x-layouts.app :title="'Laporkan '.$vendor->name">
    <x-site.header />

    <main class="mx-auto max-w-2xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <nav class="text-sm text-ink-muted" aria-label="Breadcrumb">
            <a href="{{ route('vendors.show', $vendor) }}" class="hover:text-ink">{{ $vendor->name }}</a> › <span class="text-ink">Laporkan vendor</span>
        </nav>

        <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight">Laporkan {{ $vendor->name }}</h1>
        <p class="mt-2 text-sm text-ink-muted">Admin akan menyiasat setiap laporan sebelum sebarang tindakan diambil. Laporan yang disahkan mengikut tangga tindakan: amaran, potongan point dan turun ranking, penggantungan sementara, kemudian penyingkiran.</p>

        {{-- resources/js/components/public/ReportVendorForm.vue --}}
        <div data-vue="report-vendor-form" data-props="@vueProps($props)"></div>
    </main>

    <x-site.footer />
</x-layouts.app>
