@props(['quotation'])

{{-- A quotation's badge. A sent one past its last day reads as expired,
     which is never stored. --}}
@if ($quotation->isExpired())
    <span {{ $attributes->class('inline-flex shrink-0 items-center rounded-full bg-surface-muted px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-ink-muted') }}>{{ __('pages.quotations.expired') }}</span>
@else
    <x-booking-status :status="$quotation->status" {{ $attributes }} />
@endif
