@php
    $budget = (float) $wedding->budget;
    $remaining = $budget - $totalActual;
    $used = $budget > 0 ? min(100, (int) round($totalActual / $budget * 100)) : 0;
@endphp

<x-layouts.customer title="Bajet" heading="Bajet majlis" subheading="Tetapkan bajet setiap kategori. Lajur Actual diambil terus daripada tempahan sebenar anda.">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Jumlah bajet" :value="'RM'.number_format($budget)" :hint="'Diagih RM'.number_format($totalPlanned)" />
        <x-stat-card label="Ditempah" :value="'RM'.number_format($totalActual)" hint="Jumlah semua booking aktif" />
        <x-stat-card label="Dibayar" :value="'RM'.number_format($totalPaid)" :hint="'Baki bayaran RM'.number_format($totalActual - $totalPaid)" />
        <x-stat-card label="Baki bajet" :value="'RM'.number_format($remaining)" :hint="$remaining < 0 ? 'Melebihi bajet' : 'Masih ada ruang'" />
    </div>

    <x-progress-bar label="Penggunaan bajet" :caption="'RM'.number_format($totalActual).' / RM'.number_format($budget)" :value="$totalActual" :max="$budget" :over="$totalActual > $budget" class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
        @if ($totalPlanned > $budget)
            <p class="mt-2 text-xs text-amber-700">Agihan kategori anda melebihi jumlah bajet sebanyak RM{{ number_format($totalPlanned - $budget) }}.</p>
        @endif
    </x-progress-bar>

    <section class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="min-w-0 rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Komitmen mengikut bulan</h2>
            <p class="text-sm text-ink-muted">Jumlah tempahan yang anda buat setiap bulan.</p>
            <x-chart.bars :series="$committedSeries" :format="fn ($v) => 'RM'.number_format($v)" class="mt-4" empty="Belum ada tempahan untuk dipaparkan." />
        </div>
        <div class="min-w-0 rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Bayaran mengikut bulan</h2>
            <p class="text-sm text-ink-muted">Wang yang benar-benar sudah keluar.</p>
            <x-chart.bars :series="$paidSeries" :format="fn ($v) => 'RM'.number_format($v)" class="mt-4" empty="Belum ada bayaran direkod." />
        </div>
        <div class="rounded-2xl border border-line bg-surface-raised p-5 lg:col-span-2">
            <h2 class="font-semibold">Perbelanjaan mengikut kategori</h2>
            <x-chart.donut :series="$categoryMix" :format="fn ($v) => 'RM'.number_format($v)" class="mt-4" empty="Tempah vendor untuk melihat agihan perbelanjaan anda." />
        </div>
    </section>

    <form method="POST" action="{{ route('weddings.budget.update', $wedding) }}" class="mt-6">
        @csrf
        @method('PUT')

        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 text-right font-semibold">Bajet</th>
                        <th class="px-4 py-3 text-right font-semibold">Actual</th>
                        <th class="px-4 py-3 text-right font-semibold">Beza</th>
                        <th class="px-4 py-3 font-semibold">Vendor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($rows as $row)
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap"><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" :slug="$row['category']->slug" :fallback="$row['category']->icon" /> {{ $row['category']->name }}</td>
                            <td class="px-4 py-3 text-right">
                                <label class="sr-only" for="planned-{{ $row['category']->id }}">Bajet {{ $row['category']->name }}</label>
                                <input id="planned-{{ $row['category']->id }}" type="number" step="50" min="0" name="planned[{{ $row['category']->id }}]" value="{{ (int) $row['planned'] }}" class="w-28 rounded-lg border border-line bg-surface px-2 py-1.5 text-right focus:border-brand-400 focus:outline-none">
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                @if ($row['actual'] > 0)
                                    RM{{ number_format($row['actual']) }}
                                    <span class="block text-xs text-ink-muted">dibayar RM{{ number_format($row['paid']) }}</span>
                                @else
                                    <span class="text-ink-muted">—</span>
                                @endif
                            </td>
                            <td @class(['px-4 py-3 text-right whitespace-nowrap font-medium', 'text-red-600' => $row['actual'] > 0 && $row['difference'] < 0, 'text-emerald-600' => $row['actual'] > 0 && $row['difference'] >= 0, 'text-ink-muted' => $row['actual'] <= 0])>
                                @if ($row['actual'] > 0)
                                    {{ $row['difference'] < 0 ? '+' : '−' }}RM{{ number_format(abs($row['difference'])) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @forelse ($row['bookings'] as $booking)
                                    <a href="{{ route('bookings.show', $booking) }}" class="block truncate text-xs hover:text-brand-700">{{ $booking->vendor->name }}</a>
                                @empty
                                    <a href="{{ route('vendors.index', ['category' => $row['category']->slug]) }}" class="text-xs text-ink-muted hover:text-brand-700">Cari vendor →</a>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot class="bg-surface-muted font-semibold">
                    <tr>
                        <td class="px-4 py-3">Jumlah</td>
                        <td class="px-4 py-3 text-right">RM{{ number_format($totalPlanned) }}</td>
                        <td class="px-4 py-3 text-right">RM{{ number_format($totalActual) }}</td>
                        <td @class(['px-4 py-3 text-right', 'text-red-600' => $remaining < 0, 'text-emerald-600' => $remaining >= 0])>RM{{ number_format(abs($remaining)) }} {{ $remaining < 0 ? 'lebih' : 'baki' }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <label class="flex flex-col gap-1.5 sm:w-56">
                <span class="text-sm font-medium">Jumlah bajet majlis (RM)</span>
                <input type="number" step="100" min="0" name="budget" value="{{ (int) $budget }}" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan bajet</button>
        </div>
    </form>
</x-layouts.customer>
