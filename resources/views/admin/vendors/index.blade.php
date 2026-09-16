@php use App\Enums\VendorStatus; @endphp

<x-layouts.admin title="Vendor" heading="Vendor" subheading="Luluskan, gantung dan pantau semua vendor platform.">
    <div class="no-scrollbar -mx-4 mb-6 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:px-0">
        <a href="{{ route('admin.vendors.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua ({{ $counts->sum() }})</a>
        @foreach (VendorStatus::cases() as $case)
            <a href="{{ route('admin.vendors.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </div>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.vendors.data', ['status' => $status?->value]),
            'columns' => $columns,
            'searchPlaceholder' => 'Cari nama vendor atau bandar…',
            'emptyTitle' => 'Tiada vendor sepadan',
            'emptyMessage' => 'Cuba status lain, atau kosongkan carian.',
            'csrf' => csrf_token(),
            'rowAction' => ['inline' => true],
        ])"
    ></div>
</x-layouts.admin>
