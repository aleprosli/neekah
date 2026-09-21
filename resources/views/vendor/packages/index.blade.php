<x-layouts.vendor :title="__('pages.dash.pakej')" :heading="__('pages.dash.pakej_2')" :subheading="__('pages.dash.pengantin_memilih_salah_satu_pakej')">
    <x-slot:actions>
        <a href="{{ route('vendor.packages.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ {{ __('pages.dash.tambah_pakej') }}</a>
    </x-slot:actions>

    {{-- resources/js/components/vendor/VendorPackagesPage.vue --}}
    <div
        data-vue="vendor-packages-page"
        data-props="@vueProps([
            'packages' => $packages,
            'createUrl' => route('vendor.packages.create'),
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.vendor>
