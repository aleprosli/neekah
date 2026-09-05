@php use App\Enums\VendorStatus; @endphp

<x-layouts.admin title="Vendor" heading="Vendor" subheading="Luluskan, gantung dan pantau semua vendor platform.">
    <form method="GET" action="{{ route('admin.vendors.index') }}" class="mb-6 flex flex-col gap-3">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status->value }}">
        @endif
        <div class="flex flex-wrap gap-2">
            <input type="search" size="1" name="q" value="{{ request('q') }}" placeholder="Cari nama vendor atau bandar…" class="flex-1 rounded-full border border-line bg-surface px-5 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Cari</button>
        </div>
        <div class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:px-0">
            <a href="{{ route('admin.vendors.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua ({{ $counts->sum() }})</a>
            @foreach (VendorStatus::cases() as $case)
                <a href="{{ route('admin.vendors.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }} ({{ $counts[$case->value] ?? 0 }})</a>
            @endforeach
        </div>
    </form>

    @if ($vendors->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Tiada vendor sepadan.</p>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Vendor</th>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 font-semibold">Lokasi</th>
                        <th class="px-4 py-3 font-semibold">Tahap</th>
                        <th class="px-4 py-3 text-right font-semibold">Score</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($vendors as $vendor)
                        <tr class="transition hover:bg-surface-muted/60">
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.vendors.show', $vendor) }}" class="font-medium hover:text-brand-700">{{ $vendor->name }}</a>
                                <p class="text-xs text-ink-muted">{{ $vendor->user->email }}</p>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap"><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" :slug="$vendor->category->slug" :fallback="$vendor->category->icon" /> {{ $vendor->category->name }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $vendor->city }}, {{ $vendor->state }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $vendor->tier->label() }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $vendor->score, 1) }}</td>
                            <td class="px-4 py-3">
                                <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $vendor->status === VendorStatus::Approved, 'bg-amber-100 text-amber-800' => $vendor->status === VendorStatus::Pending, 'bg-surface-muted text-ink-muted' => in_array($vendor->status, [VendorStatus::Rejected, VendorStatus::Suspended], true)])>{{ $vendor->status->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if ($vendor->status !== VendorStatus::Approved)
                                    <form method="POST" action="{{ route('admin.vendors.status', $vendor) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="approved">
                                        <button type="submit" class="rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">Lulus</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.vendors.status', $vendor) }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="status" value="suspended">
                                        <button type="submit" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400">Gantung</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $vendors->links() }}</div>
    @endif
</x-layouts.admin>
