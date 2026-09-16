<x-layouts.admin title="Laporan vendor" :heading="$violation->vendor->name" :subheading="$violation->type->label().' · dilaporkan '.$violation->created_at->translatedFormat('j M Y, g:i A')">
    <x-slot:actions>
        <a href="{{ route('admin.vendors.show', $violation->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat vendor</a>
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminViolationDetail.vue --}}
    <div data-vue="admin-violation-detail" data-props="{{ json_encode($props) }}"></div>
</x-layouts.admin>
