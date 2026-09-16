@php use App\Enums\UserRole; @endphp

<x-layouts.admin title="Pengguna" heading="Pengguna" subheading="Semua akaun pengantin, vendor dan admin.">
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.users.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => ! $role, 'border-line hover:border-brand-400' => $role])>Semua ({{ $counts->sum() }})</a>
        @foreach (UserRole::cases() as $case)
            <a href="{{ route('admin.users.index', ['role' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => $role === $case, 'border-line hover:border-brand-400' => $role !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </div>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="{{ json_encode([
            'dataUrl' => route('admin.users.data', ['role' => $role?->value]),
            'columns' => $columns,
            'searchPlaceholder' => 'Cari nama atau emel…',
            'emptyTitle' => 'Tiada akaun sepadan',
            'emptyMessage' => 'Cuba peranan lain, atau kosongkan carian.',
            'csrf' => csrf_token(),
            'rowAction' => [
                'urlKey' => 'impersonate_url',
                'labelKey' => 'name',
                'label' => 'Impersonate',
                'title' => 'Log masuk sebagai __ROW__?',
                'message' => 'Anda akan melihat Neekah persis seperti pengguna ini. Pembayaran dimatikan, dan tindakan ini direkod dalam log sistem.',
                'confirmLabel' => 'Ya, impersonate',
            ],
        ]) }}"
    ></div>
</x-layouts.admin>
