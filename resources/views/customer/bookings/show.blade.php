@php use App\Enums\BookingStatus; use App\Enums\PaymentStatus; use App\Enums\PaymentType; @endphp

<x-layouts.app :title="'Tempahan '.$booking->reference">
    <x-site.header />

    <main class="mx-auto max-w-5xl px-4 pt-24 pb-24 sm:px-6 lg:px-10 lg:pt-28">
        <nav class="text-sm text-ink-muted" aria-label="Breadcrumb">
            <a href="{{ route('bookings.index') }}" class="hover:text-ink">Tempahan saya</a> › <span class="text-ink">{{ $booking->reference }}</span>
        </nav>

        @if (session('status'))
            <div class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-100">{{ session('status') }}</div>
        @endif
        @error('payment')
            <div class="mt-4 rounded-2xl bg-brand-50 px-5 py-4 text-sm text-brand-800 dark:bg-brand-900/40 dark:text-brand-100">{{ $message }}</div>
        @enderror

        <div class="mt-6 grid gap-8 lg:grid-cols-[1fr_340px]">
            <div class="flex flex-col gap-6">
                <div class="flex items-start gap-4">
                    <span class="flex size-16 shrink-0 items-center justify-center rounded-2xl bg-linear-to-br text-3xl {{ $booking->vendor->cover_tone }}">{{ $booking->vendor->category->icon }}</span>
                    <div class="flex flex-col gap-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="font-display text-2xl font-semibold tracking-tight">{{ $booking->vendor->name }}</h1>
                            <x-booking-status :status="$booking->status" />
                        </div>
                        <p class="text-sm text-ink-muted">{{ $booking->vendor->category->name }} · {{ $booking->vendor->city }}, {{ $booking->vendor->state }}</p>
                        <a href="{{ route('vendors.show', $booking->vendor) }}" class="text-sm font-medium text-brand-600 underline underline-offset-4">Lihat profil vendor</a>
                    </div>
                </div>

                <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Rujukan</dt><dd class="font-semibold">{{ $booking->reference }}</dd></div>
                    <div><dt class="text-ink-muted">Tarikh majlis</dt><dd class="font-semibold">{{ $booking->event_date->translatedFormat('l, j F Y') }}</dd></div>
                    <div><dt class="text-ink-muted">Pakej</dt><dd class="font-semibold">{{ $booking->package_name }}</dd></div>
                    <div><dt class="text-ink-muted">Dibuat pada</dt><dd class="font-semibold">{{ $booking->created_at->translatedFormat('j M Y, g:i A') }}</dd></div>
                    @if ($booking->notes)
                        <div class="sm:col-span-2"><dt class="text-ink-muted">Nota</dt><dd>{{ $booking->notes }}</dd></div>
                    @endif
                </dl>

                {{-- Timeline --}}
                <ol class="flex flex-col gap-3 rounded-2xl border border-line p-5 text-sm">
                    @foreach ([
                        ['Booking dibuat', true, $booking->created_at],
                        ['Deposit dibayar', $booking->depositPayment?->isPaid(), $booking->depositPayment?->paid_at],
                        ['Booking disahkan', $booking->confirmed_at !== null, $booking->confirmed_at],
                        ['Baki dibayar', $booking->balancePayment?->isPaid(), $booking->balancePayment?->paid_at],
                        ['Majlis selesai', $booking->completed_at !== null, $booking->completed_at],
                        ['Review diberi', $booking->review !== null, $booking->review?->created_at],
                    ] as [$label, $done, $at])
                        <li class="flex items-center gap-3">
                            <span @class(['flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold', 'bg-emerald-500 text-white' => $done, 'border border-line text-ink-muted' => ! $done])>{{ $done ? '✓' : $loop->iteration }}</span>
                            <span @class(['font-medium' => $done, 'text-ink-muted' => ! $done])>{{ $label }}</span>
                            @if ($done && $at)
                                <span class="ml-auto text-xs text-ink-muted">{{ $at->translatedFormat('j M Y') }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </div>

            {{-- Payments --}}
            <aside class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 shadow-xl shadow-brand-900/5 lg:sticky lg:top-28 lg:self-start">
                <div>
                    <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Jumlah</p>
                    <p class="font-display text-3xl font-semibold">RM{{ number_format((float) $booking->total_amount, 2) }}</p>
                    <p class="text-sm text-ink-muted">Dibayar RM{{ number_format($booking->paidAmount(), 2) }}</p>
                </div>

                <ul class="flex flex-col gap-3">
                    @foreach ($booking->payments->sortBy(fn ($payment) => $payment->type === PaymentType::Deposit ? 0 : 1) as $payment)
                        <li class="flex flex-col gap-2 rounded-xl border border-line p-4 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ $payment->type->label() }} ({{ $payment->type === PaymentType::Deposit ? '40%' : '60%' }})</span>
                                <span class="font-semibold">RM{{ number_format((float) $payment->amount, 2) }}</span>
                            </div>
                            @if ($payment->isPaid())
                                <p class="text-xs text-emerald-700 dark:text-emerald-300">✓ Dibayar {{ $payment->paid_at->translatedFormat('j M Y') }} · {{ $payment->gateway_reference }}</p>
                            @elseif ($booking->status === BookingStatus::Cancelled)
                                <p class="text-xs text-ink-muted">Dibatalkan</p>
                            @elseif ($payment->type === PaymentType::Balance && ! $booking->depositPayment?->isPaid())
                                <p class="text-xs text-ink-muted">Boleh dibayar selepas deposit</p>
                            @elseif (auth()->user()->can('pay', $booking))
                                <form method="POST" action="{{ route('bookings.payments.store', [$booking, $payment]) }}">
                                    @csrf
                                    <button type="submit" class="w-full rounded-full bg-brand-600 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Bayar {{ $payment->type->label() }} sekarang</button>
                                </form>
                                <p class="text-center text-[11px] text-ink-muted">Sandbox: bayaran diluluskan serta-merta.</p>
                            @else
                                <p class="text-xs text-ink-muted">Menunggu bayaran pelanggan</p>
                            @endif
                        </li>
                    @endforeach
                </ul>

                <p class="text-xs text-ink-muted">Komisen platform {{ number_format((float) $booking->commission_rate, 0) }}% ditolak daripada pembayaran kepada vendor.</p>
            </aside>
        </div>
    </main>

    <x-site.footer />
    <x-site.mobile-nav />
</x-layouts.app>
