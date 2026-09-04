<x-layouts.vendor title="Dashboard vendor" :heading="$vendor->name" :subheading="$vendor->category->name.' · '.$vendor->city.', '.$vendor->state.' · '.$vendor->tier->label().' Vendor'">
    <x-slot:actions>
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat profil awam</a>
        @endif
        <a href="{{ route('vendor.bookings.create') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">+ Rekod booking</a>
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Majlis akan datang" :value="$stats['upcoming']" :href="route('vendor.bookings.index', ['status' => 'confirmed'])" />
        <x-stat-card label="Menunggu deposit" :value="$stats['pending']" :href="route('vendor.bookings.index', ['status' => 'pending_payment'])" />
        <x-stat-card label="Enquiry baru" :value="$stats['open_enquiries']" :href="route('vendor.enquiries.index')" />
        <x-stat-card label="Bayaran diterima" :value="'RM'.number_format($stats['paid_total'], 2)" :hint="$stats['completed'].' majlis selesai'" />
    </div>

    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_320px]">
        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Tempahan terdekat</h2>
            @if ($upcomingBookings->isEmpty())
                <p class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">Tiada tempahan akan datang. Booking yang dibuat pelanggan atau yang anda rekod akan muncul di sini.</p>
            @else
                <ul class="divide-y divide-line rounded-2xl border border-line">
                    @foreach ($upcomingBookings as $booking)
                        <li>
                            <a href="{{ route('vendor.bookings.show', $booking) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                                <div class="w-14 shrink-0 text-center">
                                    <p class="font-display text-xl font-semibold leading-none">{{ $booking->event_date->format('j') }}</p>
                                    <p class="text-[11px] text-ink-muted uppercase">{{ $booking->event_date->translatedFormat('M') }}</p>
                                </div>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium">{{ $booking->user->name }}</p>
                                    <p class="truncate text-sm text-ink-muted">{{ $booking->package_name }} · {{ $booking->reference }}</p>
                                </div>
                                <x-booking-status :status="$booking->status" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">Lengkapkan profil</h2>
            <ul class="flex flex-col gap-2 rounded-2xl border border-line p-4">
                @foreach ($checklist as $item)
                    <li>
                        <a href="{{ $item['href'] }}" class="flex items-center gap-3 rounded-xl px-2 py-2 text-sm transition hover:bg-surface-muted">
                            <span @class(['flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold', 'bg-emerald-500 text-white' => $item['done'], 'border border-line text-ink-muted' => ! $item['done']])>{{ $item['done'] ? '✓' : '' }}</span>
                            <span @class(['text-ink-muted line-through' => $item['done']])>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <p class="text-xs text-ink-muted">Profil lengkap membantu admin meluluskan anda lebih cepat dan menaikkan Vendor Score.</p>
        </section>
    </div>
</x-layouts.vendor>
