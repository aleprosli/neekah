{{-- Step one of a new contract: which quotation it is for. Its client, date
     and sums come with it; starting without one is the second choice. --}}
<x-layouts.vendor :title="__('pages.contracts.create_heading')" :heading="__('pages.contracts.create_heading')" :subheading="__('pages.contracts.pick_subheading')">
    <div class="mx-auto flex max-w-3xl flex-col gap-4">
        <ol class="flex items-center gap-3 text-xs font-medium text-ink-muted">
            <li class="flex items-center gap-2 text-brand-700"><span class="flex size-6 items-center justify-center rounded-full bg-brand-600 text-white">1</span>{{ __('pages.contracts.step_quotation') }}</li>
            <li aria-hidden="true" class="h-px w-6 bg-line"></li>
            <li class="flex items-center gap-2"><span class="flex size-6 items-center justify-center rounded-full border border-line">2</span>{{ __('pages.contracts.step_write') }}</li>
            <li aria-hidden="true" class="h-px w-6 bg-line"></li>
            <li class="flex items-center gap-2"><span class="flex size-6 items-center justify-center rounded-full border border-line">3</span>{{ __('pages.contracts.step_send') }}</li>
        </ol>

        <ul class="flex flex-col gap-3">
            @foreach ($quotations as $quotation)
                <li>
                    <a href="{{ route('vendor.contracts.create', ['quotation' => $quotation->token]) }}" class="flex min-w-0 flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-4 transition hover:border-brand-400 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <p class="flex flex-wrap items-center gap-2 font-semibold">
                                <span class="font-mono text-sm">{{ $quotation->number }}</span>
                                <x-quotation-status :quotation="$quotation" />
                                @if ($quotation->contracts_count > 0)
                                    <span class="rounded-full bg-surface-muted px-2.5 py-1 text-xs font-medium text-ink-muted">{{ __('pages.contracts.has_contract') }}</span>
                                @endif
                            </p>
                            <p class="mt-1 text-sm break-words">{{ $quotation->client_name }}@if ($quotation->event_date) · {{ $quotation->event_date->translatedFormat('j M Y') }}@endif</p>
                        </div>
                        <div class="flex shrink-0 items-center justify-between gap-4 sm:justify-end">
                            <span class="font-semibold tabular-nums">{{ App\Models\Quotation::money($quotation->total) }}</span>
                            <span class="text-sm font-medium text-brand-700">{{ __('pages.contracts.use_quotation') }} →</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>

        <a href="{{ route('vendor.contracts.create', ['kosong' => 1]) }}" class="rounded-2xl border border-dashed border-line p-4 text-center text-sm font-medium text-ink-muted transition hover:border-brand-400 hover:text-ink">{{ __('pages.contracts.start_blank') }}</a>
    </div>
</x-layouts.vendor>
