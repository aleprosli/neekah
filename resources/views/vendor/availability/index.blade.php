<x-layouts.vendor title="Kalendar" heading="Kalendar & availability" subheading="Tutup tarikh yang anda tidak boleh terima tempahan. Tarikh yang sudah ada booking ditutup automatik.">
    <div class="grid gap-8 lg:grid-cols-[1fr_1fr]">
        <section class="flex flex-col gap-4">
            <form method="POST" action="{{ route('vendor.availability.store') }}" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                @csrf
                <h2 class="font-semibold">Tutup tarikh</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-form.field label="Dari" name="from" type="date" :value="today()->toDateString()" required />
                    <x-form.field label="Hingga (pilihan)" name="to" type="date" help="Kosongkan untuk satu hari sahaja." />
                </div>
                <x-form.field label="Sebab (pilihan)" name="reason" placeholder="Cuti, majlis luar platform, dll." />
                <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tutup tarikh</button>
            </form>

            <div class="rounded-2xl border border-line">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Tarikh ditutup</h2>
                @if ($dates->isEmpty())
                    <p class="px-5 py-6 text-sm text-ink-muted">Tiada tarikh ditutup.</p>
                @else
                    <ul class="divide-y divide-line">
                        @foreach ($dates as $date)
                            <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 text-sm">
                                <span class="font-medium">{{ $date->date->translatedFormat('D, j M Y') }}</span>
                                <span class="truncate text-ink-muted">{{ $date->reason }}</span>
                                <form method="POST" action="{{ route('vendor.availability.destroy', $date) }}" class="ml-auto">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-medium text-brand-600 hover:underline">Buka semula</button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </section>

        <section class="rounded-2xl border border-line lg:self-start">
            <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Tarikh dengan tempahan</h2>
            @if ($bookedDates->isEmpty())
                <p class="px-5 py-6 text-sm text-ink-muted">Tiada tempahan akan datang.</p>
            @else
                <ul class="divide-y divide-line">
                    @foreach ($bookedDates as $booking)
                        <li class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 text-sm">
                            <span class="font-medium">{{ $booking->event_date->translatedFormat('D, j M Y') }}</span>
                            <a href="{{ route('vendor.bookings.show', $booking) }}" class="text-ink-muted hover:text-ink">{{ $booking->reference }}</a>
                            <x-booking-status :status="$booking->status" class="ml-auto" />
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.vendor>
