<x-layouts.vendor title="Rekod booking" heading="Rekod booking" subheading="Selepas berbincang dengan pelanggan, rekod booking di sini. Pelanggan akan bayar deposit melalui akaun Neekah mereka.">
    @if ($packages->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">Anda perlu <a href="{{ route('vendor.packages.create') }}" class="font-medium text-brand-600 underline underline-offset-4">tambah pakej aktif</a> sebelum merekod booking.</p>
    @else
        <form method="POST" action="{{ route('vendor.bookings.store') }}" class="flex max-w-2xl flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            @csrf
            <x-form.field label="Emel pelanggan" name="customer_email" type="email" placeholder="aina@contoh.com" help="Pelanggan mesti sudah mendaftar akaun pengantin di Neekah." required />
            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select label="Pakej" name="package_id" required>
                    @foreach ($packages as $package)
                        <option value="{{ $package->id }}" @selected((int) old('package_id') === $package->id)>{{ $package->name }} · RM{{ number_format((float) $package->price, 2) }}</option>
                    @endforeach
                </x-form.select>
                <x-form.field label="Tarikh majlis" name="event_date" type="date" :value="old('event_date')" required />
            </div>
            <x-form.textarea label="Nota (pilihan)" name="notes" rows="3" placeholder="Lokasi, jumlah tetamu, permintaan khas" />

            <div class="rounded-xl bg-surface-muted p-4 text-sm text-ink-muted">
                Deposit 40% dan baki 60% akan dijana automatik. Komisen platform 8% ditolak daripada pembayaran kepada anda.
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Rekod booking</button>
                <a href="{{ route('vendor.bookings.index') }}" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
            </div>
        </form>
    @endif
</x-layouts.vendor>
