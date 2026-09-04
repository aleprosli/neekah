<x-layouts.customer title="Enquiry" :heading="$enquiry->vendor->name" :subheading="$enquiry->created_at->translatedFormat('j M Y, g:i A')">
    <x-slot:actions>
        <a href="{{ route('vendors.show', $enquiry->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat vendor</a>
    </x-slot:actions>

    <div class="flex max-w-3xl flex-col gap-4">
        <div class="rounded-2xl border border-line p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Mesej anda</p>
            <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ $enquiry->message }}</p>
            @if ($enquiry->event_date || $enquiry->package)
                <p class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">
                    @if ($enquiry->event_date) Tarikh: {{ $enquiry->event_date->translatedFormat('j F Y') }} @endif
                    @if ($enquiry->package) · Pakej: {{ $enquiry->package->name }} @endif
                </p>
            @endif
        </div>

        @if ($enquiry->reply)
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                <p class="text-xs font-semibold tracking-wide text-emerald-800 uppercase">Balasan {{ $enquiry->vendor->name }} · {{ $enquiry->replied_at->translatedFormat('j M Y, g:i A') }}</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ $enquiry->reply }}</p>
            </div>
            <a href="{{ route('vendors.show', $enquiry->vendor) }}#tempah" class="w-fit rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Tempah vendor ini</a>
        @else
            <p class="rounded-2xl border border-dashed border-line p-5 text-sm text-ink-muted">Menunggu balasan vendor. Kebanyakan vendor membalas dalam masa 24 jam.</p>
        @endif
    </div>
</x-layouts.customer>
