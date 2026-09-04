<x-layouts.admin title="Laporan vendor" :heading="$violation->vendor->name" :subheading="$violation->type->label().' · dilaporkan '.$violation->created_at->translatedFormat('j M Y, g:i A')">
    <x-slot:actions>
        <a href="{{ route('admin.vendors.show', $violation->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat vendor</a>
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1fr_340px]">
        <div class="flex flex-col gap-6">
            <div class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Laporan</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ $violation->description }}</p>
                <dl class="mt-4 grid gap-2 border-t border-line pt-4 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Pelapor</dt><dd class="font-medium">{{ $violation->reporter?->name ?? 'Pengguna dipadam' }}</dd><dd class="text-ink-muted">{{ $violation->reporter?->email }}</dd></div>
                    @if ($violation->booking)
                        <div><dt class="text-ink-muted">Booking</dt><dd class="font-medium"><a href="{{ route('admin.bookings.show', $violation->booking) }}" class="hover:text-brand-700">{{ $violation->booking->reference }}</a></dd></div>
                    @endif
                </dl>
            </div>

            @if ($history->isNotEmpty())
                <section class="rounded-2xl border border-line p-5">
                    <h2 class="font-semibold">Sejarah pelanggaran disahkan ({{ $history->count() }})</h2>
                    <ul class="mt-3 divide-y divide-line text-sm">
                        @foreach ($history as $past)
                            <li class="flex items-center justify-between gap-3 py-2">
                                <span>#{{ $past->offence_number }} · {{ $past->type->label() }}</span>
                                <span class="text-ink-muted">{{ $past->action?->label() }} · {{ $past->resolved_at?->translatedFormat('j M Y') }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>

        <aside class="flex flex-col gap-4">
            @if ($violation->isOpen())
                <form method="POST" action="{{ route('admin.violations.update', $violation) }}" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                    @csrf
                    @method('PUT')
                    <h2 class="text-sm font-semibold">Keputusan</h2>
                    <p class="rounded-xl bg-amber-50 p-3 text-xs text-amber-900">Jika disahkan, ini adalah pelanggaran ke-{{ $violation->vendor->violations()->upheld()->count() + 1 }}. Tindakan automatik: <strong>{{ $nextAction->label() }}</strong>.</p>
                    <x-form.textarea label="Nota admin (pilihan)" name="admin_note" rows="4" placeholder="Hasil siasatan, bukti yang disemak…" />
                    <button type="submit" name="decision" value="uphold" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Sahkan & kenakan tindakan</button>
                    <button type="submit" name="decision" value="dismiss" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">Tolak laporan</button>
                </form>
            @else
                <div class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-5 text-sm">
                    <h2 class="font-semibold">{{ $violation->status->label() }}</h2>
                    @if ($violation->action)
                        <p>Tindakan: <strong>{{ $violation->action->label() }}</strong> (pelanggaran ke-{{ $violation->offence_number }})</p>
                    @endif
                    @if ($violation->admin_note)
                        <p class="text-ink-muted">{{ $violation->admin_note }}</p>
                    @endif
                    <p class="text-xs text-ink-muted">Diselesaikan oleh {{ $violation->resolver?->name }} pada {{ $violation->resolved_at?->translatedFormat('j M Y, g:i A') }}</p>
                </div>
            @endif

            <dl class="flex flex-col gap-2 rounded-2xl border border-line p-5 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Status vendor</dt><dd class="font-medium">{{ $violation->vendor->status->label() }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Tahap</dt><dd class="font-medium">{{ $violation->vendor->tier->label() }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Penalty points</dt><dd class="font-medium">{{ $violation->vendor->penalty_points }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Vendor Score</dt><dd class="font-medium">{{ number_format((float) $violation->vendor->score, 2) }}</dd></div>
            </dl>
        </aside>
    </div>
</x-layouts.admin>
