<x-layouts.app :title="'Laporkan '.$vendor->name">
    <x-site.header />

    <main class="mx-auto max-w-2xl px-4 pt-24 pb-24 sm:px-6 lg:pt-28">
        <nav class="text-sm text-ink-muted" aria-label="Breadcrumb">
            <a href="{{ route('vendors.show', $vendor) }}" class="hover:text-ink">{{ $vendor->name }}</a> › <span class="text-ink">Laporkan vendor</span>
        </nav>

        <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight">Laporkan {{ $vendor->name }}</h1>
        <p class="mt-2 text-sm text-ink-muted">Admin akan menyiasat setiap laporan sebelum sebarang tindakan diambil. Laporan yang disahkan mengikut tangga tindakan: amaran, potongan point dan turun ranking, penggantungan sementara, kemudian penyingkiran.</p>

        @if ($errors->any())
            <ul class="mt-6 flex flex-col gap-1 rounded-2xl bg-brand-50 p-4 text-sm text-brand-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ route('vendors.report.store', $vendor) }}" class="mt-6 flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            @csrf

            <x-form.select label="Jenis pelanggaran" name="type" required>
                <option value="">Pilih jenis</option>
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </x-form.select>

            @if ($bookings->isNotEmpty())
                <x-form.select label="Booking berkaitan (pilihan)" name="booking_id">
                    <option value="">Tiada booking khusus</option>
                    @foreach ($bookings as $booking)
                        <option value="{{ $booking->id }}" @selected((int) old('booking_id') === $booking->id)>{{ $booking->reference }} · {{ $booking->event_date->translatedFormat('j M Y') }}</option>
                    @endforeach
                </x-form.select>
            @endif

            <x-form.textarea label="Apa yang berlaku?" name="description" rows="6" placeholder="Terangkan kejadian dengan seberapa terperinci yang boleh, termasuk tarikh dan cara vendor menghubungi anda." required />

            <div class="rounded-xl bg-surface-muted p-4 text-xs text-ink-muted">
                Laporan palsu boleh menjejaskan vendor yang jujur. Hantar hanya jika anda benar-benar mengalami masalah ini.
            </div>

            <div class="flex gap-2">
                <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Hantar laporan</button>
                <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
            </div>
        </form>
    </main>

    <x-site.footer />
</x-layouts.app>
