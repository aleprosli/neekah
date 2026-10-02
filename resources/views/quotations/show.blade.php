{{-- A vendor's quotation, or the invoice issued from it, as the client sees
     it: no account, the token is the key. The sheet prints on one page (see
     x-print-fit); the toolbar and the answer form never print. ?cetak=1
     opens the print dialog straight away. --}}
@php
    $title = $asInvoice ? __('pages.quotation_doc.invoice') : __('pages.quotation_doc.quotation');
    $number = $asInvoice ? $quotation->invoice_number : $quotation->number;
    $money = fn ($amount) => App\Models\Quotation::money($amount);
@endphp

<x-layouts.app :title="$title.' '.$number.' · '.$vendor->name" shell="document" :preloader="false">
    <x-print-fit />

    <div class="min-h-screen bg-ivory px-3 py-6 print:bg-white print:p-0 sm:px-6 sm:py-10">
        <div class="mx-auto mb-5 flex max-w-[210mm] flex-wrap items-center justify-between gap-3 print:hidden">
            @if ($isOwner)
                <a href="{{ route('vendor.quotations.show', $quotation) }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-ink-muted transition hover:text-ink">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
                    {{ __('pages.quotation_doc.back') }}
                </a>
            @else
                <span></span>
            @endif
            <div class="flex flex-wrap items-center justify-end gap-2">
            @if ($contract && ! $isOwner)
                <a href="{{ $contract->publicUrl() }}" class="inline-flex rounded-full border border-brand-600 bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ __('pages.quotation_doc.contract_action') }}</a>
            @elseif ($quotation->awaitsClient() && ! $isOwner)
                <a href="#jawapan" class="inline-flex rounded-full border border-brand-600 bg-white px-4 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ __('pages.quotation_doc.jump_to_answer') }}</a>
            @endif
            <button type="button" data-print class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3h10v6M7 17H4v-7h16v7h-3" /><path d="M7 14h10v7H7z" /></svg>
                {{ __('pages.quotation_doc.download_pdf') }}
            </button>
            </div>
            <p class="w-full text-right text-xs text-ink-muted">{{ __('pages.receipt.print_hint') }}</p>

            @if ($isOwner && $quotation->status === App\Enums\QuotationStatus::Draft)
                <p class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('pages.quotation_doc.draft_preview') }}</p>
            @endif
            @if (session('status'))
                <p class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</p>
            @endif
        </div>

        <x-quotation-sheet :quotation="$quotation" :as-invoice="$asInvoice" />

        {{-- The client's answer. Never printed; once given, the sheet above says so. --}}
        @if ($contract && ! $isOwner)
            <section class="mx-auto mt-6 flex max-w-[210mm] flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5 print:hidden sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="font-semibold">{{ __('pages.quotation_doc.contract_title') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('pages.quotation_doc.contract_body', ['vendor' => $vendor->name]) }}</p>
                </div>
                <a href="{{ $contract->publicUrl() }}" class="shrink-0 rounded-full bg-brand-600 px-6 py-2.5 text-center text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.quotation_doc.contract_action') }}</a>
            </section>
        @elseif ($quotation->awaitsClient() && ! $isOwner)
            <section id="jawapan" class="mx-auto mt-6 grid max-w-[210mm] gap-4 print:hidden sm:grid-cols-[minmax(0,1fr)_minmax(0,0.8fr)]">
                <form method="POST" action="{{ route('quotations.public.accept', $quotation->token) }}" class="flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
                    @csrf
                    <div>
                        <p class="font-semibold">{{ __('pages.quotation_doc.accept_title') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __('pages.quotation_doc.accept_body', ['vendor' => $vendor->name]) }}</p>
                    </div>
                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ __('pages.quotation_doc.full_name') }}</span>
                        <input type="text" name="name" value="{{ old('name', $quotation->client_name) }}" required maxlength="120" autocomplete="name" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        @error('name') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                    </label>
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="agree" value="1" required class="mt-0.5 size-4 rounded border-line text-brand-600" @checked(old('agree'))>
                        <span>{{ __('pages.quotation_doc.agree', ['number' => $quotation->number, 'total' => $money($quotation->total)]) }}</span>
                    </label>
                    @error('agree') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                    <div><button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.quotation_doc.accept_submit') }}</button></div>
                </form>

                <details class="min-w-0 self-start rounded-2xl border border-line bg-surface-raised">
                    <summary class="cursor-pointer list-none px-5 py-4 text-sm font-medium text-ink-muted [&::-webkit-details-marker]:hidden">{{ __('pages.quotation_doc.decline_title') }}</summary>
                    <form method="POST" action="{{ route('quotations.public.decline', $quotation->token) }}" class="flex flex-col gap-3 border-t border-line p-5">
                        @csrf
                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium">{{ __('pages.quotation_doc.decline_reason') }}</span>
                            <textarea name="reason" rows="3" maxlength="500" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">{{ old('reason') }}</textarea>
                        </label>
                        <div><button type="submit" class="rounded-full border border-line px-5 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.quotation_doc.decline_submit') }}</button></div>
                    </form>
                </details>
            </section>
        @elseif ($quotation->isExpired())
            <p class="mx-auto mt-6 max-w-[210mm] rounded-2xl border border-line bg-surface-raised p-5 text-sm text-ink-muted print:hidden">{{ __('pages.quotation_doc.expired_body', ['vendor' => $vendor->name]) }}</p>
        @endif

        <p class="mx-auto mt-6 max-w-[210mm] text-center text-xs text-ink-muted print:hidden">{{ __('pages.quotation_doc.powered_by') }}</p>
    </div>
</x-layouts.app>
