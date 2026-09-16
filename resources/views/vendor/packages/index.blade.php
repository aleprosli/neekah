<x-layouts.vendor title="Pakej" heading="Pakej" subheading="Pengantin memilih salah satu pakej ini semasa menempah.">
    <x-slot:actions>
        <a href="{{ route('vendor.packages.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ Tambah pakej</a>
    </x-slot:actions>

    {{-- resources/js/components/vendor/VendorPackagesPage.vue --}}
    <div
        data-vue="vendor-packages-page"
        data-props="{{ json_encode([
            'packages' => $packages,
            'createUrl' => route('vendor.packages.create'),
            'csrf' => csrf_token(),
        ]) }}"
    ></div>
</x-layouts.vendor>
