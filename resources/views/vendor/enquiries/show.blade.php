<x-layouts.vendor title="Enquiry" :heading="'Enquiry daripada '.$enquiry->user->name" :subheading="$enquiry->created_at->translatedFormat('j M Y, g:i A')">
    <div class="grid gap-6 lg:grid-cols-[1fr_300px]">
        <div class="flex flex-col gap-4">
            <div class="rounded-2xl border border-line p-5">
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Mesej</p>
                <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ $enquiry->message }}</p>
            </div>

            @if ($enquiry->reply)
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
                    <p class="text-xs font-semibold tracking-wide text-emerald-800 uppercase">Balasan anda · {{ $enquiry->replied_at->translatedFormat('j M Y, g:i A') }}</p>
                    <p class="mt-2 text-sm leading-relaxed whitespace-pre-line">{{ $enquiry->reply }}</p>
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.enquiries.update', $enquiry) }}" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                @csrf
                @method('PUT')
                <x-form.textarea :label="$enquiry->reply ? 'Kemas kini balasan' : 'Balas'" name="reply" :value="$enquiry->reply" rows="5" placeholder="Terima kasih atas enquiry anda…" required />
                <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Hantar balasan</button>
            </form>
        </div>

        <aside class="flex flex-col gap-3 rounded-2xl border border-line p-5 text-sm lg:self-start">
            <div><p class="text-ink-muted">Pelanggan</p><p class="font-medium">{{ $enquiry->user->name }}</p><p class="break-words text-ink-muted">{{ $enquiry->user->email }}@if ($enquiry->user->phone) · {{ $enquiry->user->phone }}@endif</p></div>
            @if ($enquiry->event_date)
                <div><p class="text-ink-muted">Tarikh majlis</p><p class="font-medium">{{ $enquiry->event_date->translatedFormat('l, j F Y') }}</p></div>
            @endif
            @if ($enquiry->package)
                <div><p class="text-ink-muted">Pakej diminati</p><p class="font-medium">{{ $enquiry->package->name }}</p></div>
            @endif
            @if ($enquiry->wedding)
                <div><p class="text-ink-muted">Majlis</p><p class="font-medium">{{ $enquiry->wedding->title }}</p><p class="text-ink-muted">{{ $enquiry->wedding->city }}, {{ $enquiry->wedding->state }} · Bajet RM{{ number_format((float) $enquiry->wedding->budget) }}</p></div>
            @endif
            <a href="{{ route('vendor.bookings.create') }}" class="mt-2 rounded-full border border-line px-4 py-2 text-center font-medium transition hover:border-brand-400">Rekod booking untuk pelanggan ini</a>
        </aside>
    </div>
</x-layouts.vendor>
