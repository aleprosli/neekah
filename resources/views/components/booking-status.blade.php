@props(['status'])

@php
    use App\Enums\BookingStatus;
    $classes = match ($status) {
        BookingStatus::PendingPayment => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
        BookingStatus::Confirmed => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
        BookingStatus::Completed => 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
        BookingStatus::Cancelled => 'bg-surface-muted text-ink-muted',
    };
@endphp

<span {{ $attributes->class(['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold', $classes]) }}>{{ $status->label() }}</span>
