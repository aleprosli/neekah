@php use App\Enums\UserRole; @endphp

<x-layouts.admin title="Pengguna" heading="Pengguna" subheading="Semua akaun pengantin, vendor dan admin.">
    <div class="mb-4 flex flex-wrap gap-2">
        <a href="{{ route('admin.users.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => ! $role && ! $segment, 'border-line hover:border-brand-400' => $role || $segment])>Semua ({{ $counts->sum() }})</a>
        @foreach (UserRole::cases() as $case)
            <a href="{{ route('admin.users.index', ['role' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => $role === $case, 'border-line hover:border-brand-400' => $role !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </div>

    {{-- Who signed up and then stopped short, so the team knows who to call.
         Each chip carries its own definition, because two of them could
         otherwise be read as counting the same people. --}}
    <section class="mb-6 rounded-2xl border border-line bg-surface-muted/40 p-4">
        <h2 class="font-display text-xs tracking-[0.18em] text-gold uppercase">Perlu diikuti</h2>

        <div class="mt-3 flex flex-wrap gap-2">
            @foreach ($segments as ['segment' => $case, 'total' => $total])
                <a
                    href="{{ route('admin.users.index', ['segment' => $case->value]) }}"
                    title="{{ $case->description() }}"
                    @class([
                        'group flex min-w-0 items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium',
                        'border-brand-600 bg-brand-600 text-white' => $segment === $case,
                        'border-line bg-surface hover:border-brand-400' => $segment !== $case,
                    ])
                >
                    <span class="truncate">{{ $case->label() }}</span>
                    <span @class(['shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums', 'bg-white/20' => $segment === $case, 'bg-surface-muted text-ink-muted' => $segment !== $case])>{{ number_format($total) }}</span>
                </a>
            @endforeach
        </div>

        <p class="mt-3 text-sm text-ink-muted">
            {{ $segment?->description() ?? 'Pilih satu kumpulan untuk melihat senarai akaunnya.' }}
        </p>
    </section>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.users.data', array_filter(['role' => $role?->value, 'segment' => $segment?->value])),
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
        ])"
    ></div>
</x-layouts.admin>
