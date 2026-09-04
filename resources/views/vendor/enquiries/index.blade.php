@php use App\Enums\EnquiryStatus; @endphp

<x-layouts.vendor title="Enquiry" heading="Enquiry" subheading="Balas cepat untuk kekalkan response rate yang tinggi.">
    @if ($enquiries->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Belum ada enquiry. Pengantin boleh menghantar enquiry dari halaman vendor anda.</p>
    @else
        <ul class="divide-y divide-line rounded-2xl border border-line">
            @foreach ($enquiries as $enquiry)
                <li>
                    <a href="{{ route('vendor.enquiries.show', $enquiry) }}" class="flex flex-col gap-1 p-4 transition hover:bg-surface-muted sm:flex-row sm:items-center sm:gap-4">
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-medium">{{ $enquiry->user->name }}</p>
                                @if ($enquiry->status === EnquiryStatus::Open)
                                    <span class="rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-semibold text-white">Baru</span>
                                @endif
                            </div>
                            <p class="truncate text-sm text-ink-muted">{{ $enquiry->message }}</p>
                        </div>
                        <p class="text-xs text-ink-muted sm:text-right">
                            @if ($enquiry->event_date) {{ $enquiry->event_date->translatedFormat('j M Y') }} · @endif
                            {{ $enquiry->created_at->diffForHumans() }}
                        </p>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $enquiries->links() }}</div>
    @endif
</x-layouts.vendor>
