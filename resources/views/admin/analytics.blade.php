<x-layouts.admin title="Analitik" heading="Analitik platform" :subheading="'Tempoh: '.$period->label().' terakhir'">
    <x-slot:actions>
        <x-filter-popover label="Tempoh" :active="$period->label()" align="right" width="w-48">
            <ul class="flex flex-col gap-1">
                @foreach (\App\Support\AnalyticsPeriod::CHOICES as $months => $label)
                    <li><a href="{{ route('admin.analytics', ['months' => $months]) }}" @class(['block rounded-xl px-3 py-2 text-sm', 'bg-brand-50 font-semibold text-brand-700' => $period->months === $months, 'hover:bg-surface-muted' => $period->months !== $months])>{{ $label }}</a></li>
                @endforeach
            </ul>
        </x-filter-popover>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Nilai transaksi" :value="'RM'.number_format($grossTotal)" hint="Bayaran diterima dalam tempoh" />
        <x-stat-card label="Komisen" :value="'RM'.number_format($commissionTotal)" hint="Dari tempahan yang disahkan" />
        <x-stat-card label="Tempahan baharu" :value="number_format($bookingCount)" hint="Dicipta dalam tempoh" />
        <x-stat-card
            label="Enquiry dijawab"
            :value="$enquiryCount > 0 ? round($enquiryReplied / $enquiryCount * 100).'%' : 'Tiada data'"
            :hint="$enquiryCount.' enquiry diterima'" />
    </div>

    <div class="mt-6 grid gap-4 lg:grid-cols-2">
        <section class="rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Nilai transaksi mengikut bulan</h2>
            <x-chart.bars :series="$grossSeries" :format="fn ($v) => 'RM'.number_format($v)" class="mt-4" />
        </section>

        <section class="rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Komisen mengikut bulan</h2>
            <x-chart.bars :series="$commissionSeries" :format="fn ($v) => 'RM'.number_format($v)" class="mt-4" />
        </section>

        <section class="rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Tempahan mengikut status</h2>
            <x-chart.donut :series="$bookingsByStatus" class="mt-4" />
        </section>

        <section class="rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Kategori mengikut nilai tempahan</h2>
            <x-chart.donut :series="$categoryMix" :format="fn ($v) => 'RM'.number_format($v)" class="mt-4" />
        </section>

        <section class="rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Pendaftaran pengantin</h2>
            <x-chart.line :series="$signupSeries" :format="fn ($v) => number_format($v)" class="mt-4" />
        </section>

        <section class="rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Pendaftaran vendor</h2>
            <x-chart.line :series="$vendorSignupSeries" :format="fn ($v) => number_format($v)" class="mt-4" />
        </section>
    </div>

    <section class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
        <h2 class="font-semibold">Vendor terbaik mengikut skor</h2>
        <ul class="mt-4 flex flex-col gap-2">
            @foreach ($topVendors as $vendor)
                <li class="flex items-center justify-between gap-3 text-sm">
                    <a href="{{ route('admin.vendors.show', $vendor) }}" class="min-w-0 truncate hover:text-brand-700"><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" :slug="$vendor->category->slug" :fallback="$vendor->category->icon" /> {{ $vendor->name }}</a>
                    <span class="shrink-0 text-ink-muted">{{ number_format($vendor->score, 1) }}</span>
                </li>
            @endforeach
        </ul>
    </section>
</x-layouts.admin>
