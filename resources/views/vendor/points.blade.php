@php use App\Enums\VendorTier; @endphp

<x-layouts.vendor title="Point & Ranking" heading="Point & Ranking" subheading="Ranking Neekah tidak bergantung kepada rating semata-mata. Booking sebenar, pembayaran dan perkhidmatan yang selesai adalah faktor utama.">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Performance point" :value="number_format($vendor->points_total)" :hint="$vendor->penalty_points ? '− '.$vendor->penalty_points.' penalti pelanggaran' : 'Tiada penalti'" />
        <x-stat-card label="Vendor Score" :value="number_format((float) $vendor->score, 2)" hint="Maksimum 100" />
        <x-stat-card label="Completion rate" :value="$vendor->completion_rate.'%'" :hint="$vendor->completed_bookings_count.' majlis selesai'" />
        <x-stat-card label="Response rate" :value="$vendor->responseRateLabel()" :hint="$vendor->response_rate === null ? 'Perlu sekurang-kurangnya '.\App\Models\Vendor::MIN_ENQUIRIES_FOR_RESPONSE_RATE.' enquiry untuk diukur' : 'Enquiry yang anda balas'" />
    </div>

    {{-- Analytics --}}
    <section class="mt-8">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-xl font-semibold">Prestasi {{ $period->label() }} terakhir</h2>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Support\AnalyticsPeriod::CHOICES as $months => $label)
                    <a href="{{ route('vendor.points.index', ['months' => $months]) }}" @class(['rounded-full border px-4 py-1.5 text-xs font-medium transition', 'border-brand-600 bg-brand-600 text-white' => $period->months === $months, 'border-line hover:border-brand-400' => $period->months !== $months])>{{ $label }}</a>
                @endforeach
            </div>
        </div>

        <div class="mt-4 grid gap-4 sm:grid-cols-3">
            <x-stat-card label="Pendapatan" :value="'RM'.number_format($revenueTotal)" hint="Bayaran diterima dalam tempoh" />
            <x-stat-card label="Enquiry dibalas" :value="$enquiryCount > 0 ? $enquiryReplied.' / '.$enquiryCount : 'Tiada enquiry'" hint="Enquiry yang anda terima" />
            <x-stat-card
                label="Enquiry jadi tempahan"
                :value="$enquiryCount > 0 ? round($enquiryToBookings / $enquiryCount * 100).'%' : 'Tiada data'"
                :hint="$enquiryToBookings.' tempahan dalam tempoh'" />
        </div>

        <div class="mt-4 grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <h3 class="text-sm font-semibold">Pendapatan mengikut bulan</h3>
                <x-chart.bars :series="$revenueSeries" :format="fn ($v) => 'RM'.number_format($v)" class="mt-4" />
            </div>
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <h3 class="text-sm font-semibold">Majlis selesai</h3>
                <x-chart.bars :series="$completedSeries" :format="fn ($v) => number_format($v)" class="mt-4" />
            </div>
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <h3 class="text-sm font-semibold">Rating dari masa ke masa</h3>
                <x-chart.line :series="$ratingSeries" :format="fn ($v) => number_format($v, 1)" class="mt-4" empty="Belum cukup review untuk menunjukkan aliran." />
            </div>
        </div>
    </section>

    {{-- Tier ladder --}}
    <section class="mt-8 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">Tahap anda</h2>
        <ol class="flex flex-col gap-2 sm:flex-row sm:gap-3">
            @foreach (VendorTier::cases() as $case)
                @php $reached = $case->rank() <= $vendor->tier->rank(); @endphp
                <li @class([
                    'flex flex-1 items-center gap-3 rounded-2xl border p-4 sm:flex-col sm:items-start sm:gap-1',
                    'border-brand-300 bg-brand-50/60' => $case === $vendor->tier,
                    'border-line bg-surface-raised' => $case !== $vendor->tier,
                ])>
                    <span @class([
                        'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                        'bg-brand-600 text-white' => $reached,
                        'border border-line text-ink-muted' => ! $reached,
                    ])>{{ $reached ? '✓' : $case->rank() + 1 }}</span>
                    <span @class(['text-sm font-semibold', 'text-brand-700' => $case === $vendor->tier])>
                        @if ($case === VendorTier::Recommended)🏆 @endif{{ $case->label() }}
                    </span>
                </li>
            @endforeach
        </ol>

        @if ($vendor->tier_locked)
            <p class="rounded-2xl border border-line bg-surface-muted p-4 text-sm text-ink-muted">Tahap anda telah dikunci oleh admin. Hubungi kami jika anda rasa ia perlu disemak semula.</p>
        @elseif ($nextTier)
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <h3 class="text-sm font-semibold">Untuk naik ke {{ $nextTier->label() }} Vendor</h3>
                <ul class="mt-3 flex flex-col gap-2 text-sm">
                    @foreach ($requirements as $requirement)
                        <li class="flex items-center gap-3">
                            <span @class([
                                'flex size-5 shrink-0 items-center justify-center rounded-full text-[10px] font-semibold',
                                'bg-emerald-500 text-white' => $requirement['met'],
                                'border border-line text-ink-muted' => ! $requirement['met'],
                            ])>{{ $requirement['met'] ? '✓' : '' }}</span>
                            <span @class(['text-ink-muted' => $requirement['met']])>{{ $requirement['label'] }}</span>
                            <span class="ml-auto font-medium">{{ $requirement['current'] }} <span class="text-ink-muted">/ {{ $requirement['target'] }}</span></span>
                        </li>
                    @endforeach
                </ul>
                @if ($nextTier === VendorTier::Recommended)
                    <p class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">Recommended Vendor juga memerlukan rekod bersih tanpa pelanggaran disahkan dalam tempoh 6 bulan terakhir.</p>
                @endif
            </div>
        @else
            <p class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">🏆 Anda berada di tahap tertinggi. Kekalkan rating, response rate dan rekod bersih untuk terus disyorkan.</p>
        @endif
    </section>

    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        {{-- How points are earned --}}
        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Cara point diberi</h2>
            <div class="overflow-hidden rounded-2xl border border-line">
                <table class="w-full text-sm">
                    <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Aktiviti</th>
                            <th class="px-4 py-3 text-right font-semibold">Point</th>
                            <th class="px-4 py-3 text-right font-semibold">Anda</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($earnable as $reason)
                            @php $row = $breakdown[$reason->value] ?? null; @endphp
                            <tr>
                                <td class="px-4 py-3">{{ $reason->label() }}</td>
                                <td class="px-4 py-3 text-right text-ink-muted">+{{ $reason->points() }}</td>
                                <td class="px-4 py-3 text-right font-medium">
                                    @if ($row)
                                        +{{ number_format($row->total) }}
                                        @if (! $reason->isMilestone())<span class="text-xs font-normal text-ink-muted">({{ $row->awards }}×)</span>@endif
                                    @else
                                        <span class="text-ink-muted">—</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <p class="text-xs text-ink-muted">Point tidak diberikan hanya kerana menerima enquiry. Booking sebenar, pembayaran dan perkhidmatan yang selesai menjadi faktor utama.</p>
        </section>

        {{-- Recent awards --}}
        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Sejarah point</h2>
            @if ($history->isEmpty())
                <p class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">Belum ada point. Lengkapkan profil dan pakej untuk mula mengumpul.</p>
            @else
                <ul class="divide-y divide-line rounded-2xl border border-line">
                    @foreach ($history as $point)
                        <li class="flex items-center gap-3 p-4 text-sm">
                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium">{{ $point->reason->label() }}</p>
                                <p class="text-xs text-ink-muted">{{ $point->created_at->translatedFormat('j M Y, g:i A') }}</p>
                            </div>
                            <span @class(['font-display font-semibold', 'text-emerald-600' => $point->points > 0, 'text-red-600' => $point->points < 0])>{{ $point->points > 0 ? '+' : '' }}{{ $point->points }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.vendor>
