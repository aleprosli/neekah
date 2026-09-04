@php use App\Enums\ViolationStatus; @endphp

<x-layouts.admin title="Laporan vendor" heading="Laporan vendor" subheading="Setiap laporan disemak sebelum tindakan dikenakan.">
    <div class="mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.violations.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua ({{ $counts->sum() }})</a>
        @foreach (ViolationStatus::cases() as $case)
            <a href="{{ route('admin.violations.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
        @endforeach
    </div>

    @if ($violations->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Tiada laporan. Bagus!</p>
    @else
        <ul class="divide-y divide-line rounded-2xl border border-line">
            @foreach ($violations as $violation)
                <li>
                    <a href="{{ route('admin.violations.show', $violation) }}" class="flex flex-col gap-2 p-4 transition hover:bg-surface-muted sm:flex-row sm:items-center sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <p class="font-medium">{{ $violation->vendor->name }}</p>
                                @if ($violation->isOpen())
                                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-800">Perlu semakan</span>
                                @elseif ($violation->action)
                                    <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-semibold text-ink-muted">{{ $violation->action->label() }}</span>
                                @else
                                    <span class="rounded-full bg-surface-muted px-2 py-0.5 text-[11px] font-semibold text-ink-muted">{{ $violation->status->label() }}</span>
                                @endif
                            </div>
                            <p class="mt-0.5 text-sm text-ink-muted">{{ $violation->type->label() }} · dilaporkan oleh {{ $violation->reporter?->name ?? 'pengguna dipadam' }}</p>
                            <p class="truncate text-sm text-ink-muted">{{ $violation->description }}</p>
                        </div>
                        <span class="text-xs text-ink-muted">{{ $violation->created_at->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $violations->links() }}</div>
    @endif
</x-layouts.admin>
