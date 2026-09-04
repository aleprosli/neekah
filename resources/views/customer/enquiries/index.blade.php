@php use App\Enums\EnquiryStatus; @endphp

<x-layouts.customer title="Enquiry saya" heading="Enquiry" subheading="Soalan yang anda hantar kepada vendor dan balasan mereka.">
    @if ($enquiries->isEmpty())
        <div class="flex flex-col items-center gap-3 rounded-3xl border border-dashed border-line px-6 py-16 text-center">
            <span class="text-4xl">💬</span>
            <h2 class="text-lg font-semibold">Belum ada enquiry</h2>
            <p class="max-w-sm text-sm text-ink-muted">Tanya vendor tentang pakej, tarikh atau harga sebelum menempah.</p>
            <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
        </div>
    @else
        <ul class="divide-y divide-line rounded-2xl border border-line">
            @foreach ($enquiries as $enquiry)
                <li>
                    <a href="{{ route('enquiries.show', $enquiry) }}" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                        <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-linear-to-br text-xl {{ $enquiry->vendor->cover_tone }}">{{ $enquiry->vendor->category->icon }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <p class="truncate font-medium">{{ $enquiry->vendor->name }}</p>
                                @if ($enquiry->status === EnquiryStatus::Replied)
                                    <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">Dibalas</span>
                                @endif
                            </div>
                            <p class="truncate text-sm text-ink-muted">{{ $enquiry->message }}</p>
                        </div>
                        <span class="hidden text-xs text-ink-muted sm:block">{{ $enquiry->created_at->diffForHumans() }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
        <div class="mt-6">{{ $enquiries->links() }}</div>
    @endif
</x-layouts.customer>
