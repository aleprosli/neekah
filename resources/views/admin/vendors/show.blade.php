@php use App\Enums\VendorStatus; use App\Enums\VendorTier; @endphp

<x-layouts.admin :title="$vendor->name" :heading="$vendor->name" :subheading="$vendor->category->name.' · '.$vendor->city.', '.$vendor->state">
    <x-slot:actions>
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat profil awam</a>
        @endif
        <form method="POST" action="{{ route('admin.users.impersonate', $vendor->user) }}" onsubmit="return confirm('Log masuk sebagai {{ $vendor->user->name }}? Tindakan ini direkod dalam log.')">
            @csrf
            <button type="submit" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700">Impersonate pemilik</button>
        </form>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="flex flex-col gap-6">
            <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-muted">Pemilik</dt><dd class="font-semibold">{{ $vendor->user->name }}</dd><dd class="text-ink-muted">{{ $vendor->user->email }}@if ($vendor->user->phone) · {{ $vendor->user->phone }}@endif</dd></div>
                <div><dt class="text-ink-muted">Didaftar</dt><dd class="font-semibold">{{ $vendor->created_at->translatedFormat('j M Y') }}</dd></div>
                <div><dt class="text-ink-muted">Rating</dt><dd class="font-semibold">★ {{ number_format($vendor->rating_avg, 2) }} ({{ $vendor->reviews_count }} review)</dd></div>
                <div><dt class="text-ink-muted">Booking</dt><dd class="font-semibold">{{ $bookingCount }} jumlah · {{ $vendor->completed_bookings_count }} selesai</dd></div>
                <div><dt class="text-ink-muted">Harga bermula</dt><dd class="font-semibold">RM{{ number_format((float) $vendor->price_from, 2) }} / {{ $vendor->price_unit->label() }}</dd></div>
                <div><dt class="text-ink-muted">Vendor Score</dt><dd class="font-semibold">{{ number_format((float) $vendor->score, 2) }}</dd></div>
                @if ($vendor->tagline)
                    <div class="sm:col-span-2"><dt class="text-ink-muted">Tagline</dt><dd>{{ $vendor->tagline }}</dd></div>
                @endif
                @if ($vendor->description)
                    <div class="sm:col-span-2"><dt class="text-ink-muted">Penerangan</dt><dd class="leading-relaxed">{{ $vendor->description }}</dd></div>
                @endif
            </dl>

            <section class="rounded-2xl border border-line p-5">
                <h2 class="font-semibold">Pakej ({{ $vendor->packages->count() }})</h2>
                @if ($vendor->packages->isEmpty())
                    <p class="mt-2 text-sm text-ink-muted">Vendor belum menambah pakej.</p>
                @else
                    <ul class="mt-3 divide-y divide-line text-sm">
                        @foreach ($vendor->packages as $package)
                            <li class="flex items-center justify-between gap-3 py-2">
                                <span>{{ $package->name }} <span class="text-ink-muted">· {{ $package->duration }}</span></span>
                                <span class="font-medium">RM{{ number_format((float) $package->price, 2) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section class="rounded-2xl border border-line p-5">
                <h2 class="font-semibold">Portfolio ({{ $vendor->portfolioItems->count() }})</h2>
                @if ($vendor->portfolioItems->isEmpty())
                    <p class="mt-2 text-sm text-ink-muted">Belum ada gambar portfolio.</p>
                @else
                    <ul class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-5">
                        @foreach ($vendor->portfolioItems->take(10) as $item)
                            <li><img src="{{ $item->url() }}" alt="" class="aspect-square w-full rounded-xl object-cover"></li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside class="flex flex-col gap-4">
            <div class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="text-sm font-semibold">Status: {{ $vendor->status->label() }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach (VendorStatus::cases() as $case)
                        @continue($case === $vendor->status)
                        <form method="POST" action="{{ route('admin.vendors.status', $vendor) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $case->value }}">
                            <button type="submit" @class(['rounded-full px-4 py-2 text-sm font-medium transition', 'bg-brand-600 text-white hover:bg-brand-700' => $case === VendorStatus::Approved, 'border border-line hover:border-brand-400' => $case !== VendorStatus::Approved])>{{ $case->label() }}</button>
                        </form>
                    @endforeach
                </div>
            </div>

            <form method="POST" action="{{ route('admin.vendors.tier', $vendor) }}" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                @csrf
                @method('PUT')
                <h2 class="text-sm font-semibold">Ranking</h2>
                <x-form.select label="Tahap vendor" name="tier" required>
                    @foreach (VendorTier::cases() as $case)
                        <option value="{{ $case->value }}" @selected($vendor->tier === $case)>{{ $case->label() }}</option>
                    @endforeach
                </x-form.select>
                <x-form.field label="Response rate (%)" name="response_rate" type="number" min="0" max="100" :value="$vendor->response_rate" />
                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan ranking</button>
                <p class="text-xs text-ink-muted">Vendor Score dikira semula: rating 30%, booking selesai 20%, response rate 15%, tahap 35%.</p>
            </form>
        </aside>
    </div>
</x-layouts.admin>
