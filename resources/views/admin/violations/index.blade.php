@php use App\Enums\ViolationStatus; @endphp

<x-layouts.admin title="Laporan vendor" heading="Laporan vendor" subheading="Setiap laporan disemak sebelum tindakan dikenakan.">
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.violations.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua ({{ $counts->sum() }})</a>
        @foreach (ViolationStatus::cases() as $case)
            <a href="{{ route('admin.violations.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </div>

    {{-- resources/js/components/admin/AdminViolationsPage.vue --}}
    <div
        data-vue="admin-violations-page"
        data-props="{{ json_encode([
            'violations' => $violations->items(),
            'pagination' => $violations->hasPages() ? (string) $violations->links() : '',
        ]) }}"
    ></div>
</x-layouts.admin>
