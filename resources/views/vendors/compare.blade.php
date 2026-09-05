@php use App\Enums\VendorTier; @endphp

<x-layouts.app title="Banding vendor">
    <x-site.header />

    <main class="mx-auto max-w-6xl px-4 pt-24 pb-24 sm:px-6 lg:px-10 lg:pt-28">
        <div class="flex flex-col gap-1">
            <h1 class="font-display text-3xl font-semibold tracking-tight">Banding vendor</h1>
            <p class="text-sm text-ink-muted">
                @if ($sharedCategory)
                    <x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" :slug="$sharedCategory->slug" :fallback="$sharedCategory->icon" /> {{ $sharedCategory->name }} · {{ $vendors->count() }} vendor dibandingkan
                @else
                    Pilih sehingga {{ \App\Http\Controllers\VendorComparisonController::MAX_VENDORS }} vendor daripada marketplace.
                @endif
            </p>
        </div>

        @if ($vendors->isEmpty())
            <div class="mt-10 flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
                <span class="text-4xl">⚖️</span>
                <h2 class="font-display text-xl font-semibold">Belum ada vendor dipilih</h2>
                <p class="max-w-md text-sm text-ink-muted">Di marketplace, tandakan kotak "Banding" pada kad vendor. Anda boleh membandingkan harga, pakej, rating dan prestasi mereka bersebelahan.</p>
                <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
            </div>
        @else
            @php $columns = 'grid-cols-['.str_repeat('1fr_', $vendors->count()).']'; @endphp

            <div class="mt-8 overflow-x-auto">
                <table class="w-full min-w-[40rem] border-separate border-spacing-0 text-sm">
                    <thead>
                        <tr>
                            <th class="sticky left-0 z-10 w-32 bg-surface p-3 text-left align-bottom text-xs font-semibold tracking-wide text-ink-muted uppercase">Perbandingan</th>
                            @foreach ($vendors as $vendor)
                                <th class="p-3 align-bottom">
                                    <a href="{{ route('vendors.show', $vendor) }}" class="group flex flex-col gap-2 text-left">
                                        <span class="relative block aspect-[4/3] overflow-hidden rounded-2xl bg-linear-to-br {{ $vendor->cover_tone }}">
                                            @if ($vendor->tier === VendorTier::Recommended)
                                                <span class="absolute top-2 left-2 rounded-full bg-gold-300 px-2 py-0.5 text-[11px] font-semibold text-brand-900">🏆</span>
                                            @endif
                                        </span>
                                        <span class="font-semibold group-hover:text-brand-700">{{ $vendor->name }}</span>
                                    </a>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            <tr class="border-t border-line">
                                <th class="sticky left-0 z-10 border-t border-line bg-surface p-3 text-left font-medium text-ink-muted">{{ $row['label'] }}</th>
                                @foreach ($row['values'] as $index => $value)
                                    <td @class([
                                        'border-t border-line p-3',
                                        'bg-emerald-50 font-semibold text-emerald-900' => $row['best'] === $index,
                                    ])>
                                        {{ $value }}
                                        @if ($row['best'] === $index)
                                            <span class="ml-1 text-xs font-normal text-emerald-700">terbaik</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        {{-- Packages --}}
                        <tr class="border-t border-line">
                            <th class="sticky left-0 z-10 border-t border-line bg-surface p-3 text-left align-top font-medium text-ink-muted">Pakej</th>
                            @foreach ($vendors as $vendor)
                                <td class="border-t border-line p-3 align-top">
                                    @forelse ($vendor->packages as $package)
                                        <div class="mb-2 last:mb-0">
                                            <p class="font-medium">{{ $package->name }}</p>
                                            <p class="text-xs text-ink-muted">RM{{ number_format((float) $package->price) }} · {{ $package->duration }}</p>
                                        </div>
                                    @empty
                                        <span class="text-ink-muted">Tiada pakej</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>

                        <tr class="border-t border-line">
                            <th class="sticky left-0 z-10 border-t border-line bg-surface p-3"></th>
                            @foreach ($vendors as $vendor)
                                <td class="border-t border-line p-3">
                                    <a href="{{ route('vendors.show', $vendor) }}#tempah" class="inline-flex rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700">Tempah</a>
                                </td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('vendors.index', $sharedCategory ? ['category' => $sharedCategory->slug] : []) }}" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">Tambah vendor lain</a>
                <a href="{{ route('vendors.compare') }}" class="rounded-full px-5 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Kosongkan</a>
            </div>
        @endif
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
