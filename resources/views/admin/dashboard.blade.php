<x-layouts.admin title="Admin" heading="Ringkasan platform" subheading="Semua nombor di bawah adalah data langsung daripada pangkalan data.">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Pengantin" :value="number_format($stats['customers'])" hint="Akaun customer" :href="route('admin.users.index', ['role' => 'customer'])" />
        <x-stat-card label="Vendor" :value="number_format($stats['vendors'])" :hint="$stats['pending_vendors'].' menunggu kelulusan'" :href="route('admin.vendors.index')" />
        <x-stat-card label="Tempahan" :value="number_format($stats['bookings'])" :hint="$stats['active_bookings'].' aktif · '.$stats['completed_bookings'].' selesai'" :href="route('admin.bookings.index')" />
        <x-stat-card label="Komisen platform" :value="'RM'.number_format($stats['commission'], 2)" :hint="'GTV RM'.number_format($stats['gross'], 2)" :href="route('admin.transactions.index')" />
    </div>

    @if ($stats['open_violations'])
        <a href="{{ route('admin.violations.index', ['status' => 'open']) }}" class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 transition hover:border-amber-400">
            <span class="text-lg">⚠️</span>
            <span><strong>{{ $stats['open_violations'] }}</strong> laporan vendor menunggu semakan anda.</span>
        </a>
    @endif

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <section class="flex flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">Menunggu kelulusan</h2>
                <a href="{{ route('admin.vendors.index', ['status' => 'pending']) }}" class="text-sm font-medium text-brand-600 hover:underline">Semua</a>
            </div>
            @if ($pendingVendors->isEmpty())
                <p class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">Tiada permohonan vendor baharu.</p>
            @else
                <ul class="divide-y divide-line rounded-2xl border border-line">
                    @foreach ($pendingVendors as $vendor)
                        <li class="flex items-center gap-3 p-4">
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-lg {{ $vendor->cover_tone }}">{{ $vendor->category->icon }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.vendors.show', $vendor) }}" class="block truncate font-medium hover:text-brand-700">{{ $vendor->name }}</a>
                                <p class="truncate text-xs text-ink-muted">{{ $vendor->category->name }} · {{ $vendor->city }}, {{ $vendor->state }}</p>
                            </div>
                            <form method="POST" action="{{ route('admin.vendors.status', $vendor) }}">
                                @csrf
                                <input type="hidden" name="status" value="approved">
                                <button type="submit" class="rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">Lulus</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Vendor teratas</h2>
            <ol class="divide-y divide-line rounded-2xl border border-line">
                @foreach ($topVendors as $vendor)
                    <li class="flex items-center gap-3 p-4 text-sm">
                        <span class="w-5 shrink-0 text-center font-semibold text-ink-muted">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('admin.vendors.show', $vendor) }}" class="block truncate font-medium hover:text-brand-700">{{ $vendor->name }}</a>
                            <p class="truncate text-xs text-ink-muted">{{ $vendor->tier->label() }} · ★ {{ number_format($vendor->rating_avg, 1) }} ({{ $vendor->reviews_count }})</p>
                        </div>
                        <span class="font-display font-semibold">{{ number_format((float) $vendor->score, 1) }}</span>
                    </li>
                @endforeach
            </ol>
        </section>
    </div>

    <section class="mt-8 flex flex-col gap-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold">Tempahan terkini</h2>
            <a href="{{ route('admin.bookings.index') }}" class="text-sm font-medium text-brand-600 hover:underline">Semua</a>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Rujukan</th>
                        <th class="px-4 py-3 font-semibold">Vendor</th>
                        <th class="px-4 py-3 font-semibold">Pengantin</th>
                        <th class="px-4 py-3 text-right font-semibold">Jumlah</th>
                        <th class="px-4 py-3 text-right font-semibold">Komisen</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($recentBookings as $booking)
                        <tr class="transition hover:bg-surface-muted/60">
                            <td class="px-4 py-3"><a href="{{ route('admin.bookings.show', $booking) }}" class="font-medium hover:text-brand-700">{{ $booking->reference }}</a></td>
                            <td class="px-4 py-3">{{ $booking->vendor->name }}</td>
                            <td class="px-4 py-3">{{ $booking->user->name }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">RM{{ number_format((float) $booking->total_amount, 2) }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">RM{{ number_format((float) $booking->commission_amount, 2) }}</td>
                            <td class="px-4 py-3"><x-booking-status :status="$booking->status" /></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-layouts.admin>
