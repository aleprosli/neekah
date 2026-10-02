{{-- A vendor's contract as the client sees it: read, signed here with no
     account, printed or saved as a one-page PDF (x-print-fit). The toolbar
     and the sign form never print. ?cetak=1 opens the print dialog. --}}
@php
    use App\Enums\ContractStatus;
@endphp

<x-layouts.app :title="__('pages.contract_doc.contract').' '.$contract->number.' · '.$vendor->name" shell="document" :preloader="false">
    <x-print-fit />

    <div class="min-h-screen bg-ivory px-3 py-6 print:bg-white print:p-0 sm:px-6 sm:py-10">
        <div class="mx-auto mb-5 flex max-w-[210mm] flex-wrap items-center justify-between gap-3 print:hidden">
            @if ($isOwner)
                <a href="{{ route('vendor.contracts.show', $contract) }}" class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm font-medium text-ink-muted transition hover:text-ink">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 18l-6-6 6-6" /></svg>
                    {{ __('pages.quotation_doc.back') }}
                </a>
            @else
                <span></span>
            @endif
            <button type="button" data-print class="inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 9V3h10v6M7 17H4v-7h16v7h-3" /><path d="M7 14h10v7H7z" /></svg>
                {{ __('pages.quotation_doc.download_pdf') }}
            </button>
            <p class="w-full text-right text-xs text-ink-muted">{{ __('pages.receipt.print_hint') }}</p>

            @if ($isOwner && $contract->status === ContractStatus::Draft)
                <p class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('pages.contract_doc.draft_preview') }}</p>
            @endif
            @if ($contract->status === ContractStatus::Void)
                <p class="w-full rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ __('pages.contract_doc.void_notice', ['vendor' => $vendor->name]) }}</p>
            @endif
            @if (session('status'))
                <p class="w-full rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('status') }}</p>
            @endif
        </div>

        <x-contract-sheet :contract="$contract" />

        {{-- Signing. Never printed; once signed, the sheet above carries it. --}}
        @if ($contract->awaitsSignature() && ! $isOwner)
            <form id="tandatangan" method="POST" action="{{ route('contracts.public.sign', $contract->token) }}" class="mx-auto mt-6 flex max-w-[210mm] flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 print:hidden">
                @csrf
                <div>
                    <p class="font-semibold">{{ __('pages.contract_doc.sign_title') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('pages.contract_doc.sign_body') }}</p>
                </div>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ __('pages.quotation_doc.full_name') }}</span>
                    <input type="text" name="name" value="{{ old('name', $contract->client_name) }}" required maxlength="120" autocomplete="name" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    @error('name') <span class="text-xs text-red-700">{{ $message }}</span> @enderror
                </label>

                {{-- resources/js/components/public/ContractSignaturePad.vue --}}
                <div data-vue="contract-signature-pad" data-props="@vueProps([
                    'name' => 'signature',
                    'label' => __('pages.contract_doc.signature'),
                    'hint' => __('pages.contract_doc.signature_hint'),
                    'clearLabel' => __('pages.contract_doc.signature_clear'),
                    'error' => $errors->first('signature') ?: null,
                ])">
                    <p class="rounded-xl border border-dashed border-line p-4 text-sm text-ink-muted">{{ __('pages.contract_doc.needs_javascript') }}</p>
                </div>

                <label class="flex items-start gap-3 text-sm">
                    <input type="checkbox" name="agree" value="1" required class="mt-0.5 size-4 rounded border-line text-brand-600" @checked(old('agree'))>
                    <span>{{ __('pages.contract_doc.agree', ['number' => $contract->number, 'vendor' => $vendor->name]) }}</span>
                </label>
                @error('agree') <span class="text-xs text-red-700">{{ $message }}</span> @enderror

                <div><button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.contract_doc.sign_submit') }}</button></div>
            </form>
        @endif

        <p class="mx-auto mt-6 max-w-[210mm] text-center text-xs text-ink-muted print:hidden">{{ __('pages.quotation_doc.powered_by') }}</p>
    </div>
</x-layouts.app>
