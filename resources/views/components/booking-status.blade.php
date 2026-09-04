@props(['status'])

@php
    use App\Enums\BookingStatus;
    $classes = match ($status) {
        BookingStatus::PendingPayment => 'bg-amber-100 text-amber-800',
        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-800',
        BookingStatus::Completed => 'bg-sky-100 text-sky-800',
        BookingStatus::Cancelled => 'bg-surface-muted text-ink-muted',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', $classes]) }}>{{ $status->label() }}</span>
