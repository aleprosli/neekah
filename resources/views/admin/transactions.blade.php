@php use App\Enums\PaymentStatus; @endphp

<x-layouts.admin title="Kewangan" heading="Kewangan" subheading="Nilai transaksi kasar, komisen platform dan payout vendor.">
    {{-- resources/js/components/admin/AdminStatRow.vue --}}
    <div data-vue="admin-stat-row" data-props="@vueProps(['stats' => $stats])"></div>

    <div class="mt-6 mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.transactions.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua</a>
        @foreach (PaymentStatus::cases() as $case)
            <a href="{{ route('admin.transactions.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }}</a>
        @endforeach
    </div>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.transactions.data', ['status' => $status?->value]),
            'columns' => $columns,
            'searchPlaceholder' => 'Cari rujukan, booking atau vendor…',
            'emptyTitle' => 'Tiada transaksi',
            'emptyMessage' => 'Bayaran yang diterima akan muncul di sini.',
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.admin>
