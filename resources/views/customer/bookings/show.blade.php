@php use App\Enums\BookingStatus; use App\Enums\PaymentType; use App\Models\Review; @endphp

<x-layouts.customer :title="'Tempahan '.$booking->reference" :heading="$booking->vendor->name" :subheading="$booking->package_name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
        <a href="{{ route('vendors.show', $booking->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat vendor</a>
    </x-slot:actions>

    @error('payment')
        <div class="mb-6 rounded-2xl bg-brand-50 px-5 py-4 text-sm text-brand-800">{{ $message }}</div>
    @enderror

    <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
        <div class="flex flex-col gap-6">
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

            {{-- Review --}}
            @if ($booking->review)
                <section class="rounded-2xl border border-line p-5">
                    <h2 class="font-display text-lg font-semibold">Review anda</h2>
                    <p class="mt-2 text-gold-500">{{ str_repeat('★', $booking->review->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $booking->review->rating) }}</span></p>
                    <p class="mt-2 text-sm leading-relaxed">{{ $booking->review->comment }}</p>
                </section>
            @elseif ($booking->canBeReviewed())
                <section class="flex flex-col gap-4 rounded-2xl border border-brand-200 bg-brand-50/50 p-5">
                    <div>
                        <h2 class="font-display text-lg font-semibold">Beri review</h2>
                        <p class="text-sm text-ink-muted">Majlis anda telah selesai. Kongsi pengalaman anda dengan pengantin lain.</p>
                    </div>
                    <form method="POST" action="{{ route('bookings.review.store', $booking) }}" class="flex flex-col gap-4">
                        @csrf
                        @foreach (['rating' => 'Keseluruhan', 'quality' => 'Kualiti', 'service' => 'Servis', 'communication' => 'Komunikasi', 'value' => 'Nilai', 'punctuality' => 'Ketepatan masa'] as $field => $label)
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span @class(['text-sm', 'font-semibold' => $field === 'rating'])>{{ $label }}</span>
                                <div class="flex gap-1">
                                    @foreach (range(1, 5) as $score)
                                        <label class="cursor-pointer">
                                            <input type="radio" name="{{ $field }}" value="{{ $score }}" class="peer sr-only" @checked((int) old($field, 5) === $score) required>
                                            <span class="flex size-9 items-center justify-center rounded-lg border border-line text-sm transition peer-checked:border-brand-600 peer-checked:bg-brand-600 peer-checked:text-white">{{ $score }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                        <textarea name="comment" rows="4" required placeholder="Ceritakan pengalaman anda dengan vendor ini…" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">{{ old('comment') }}</textarea>
                        <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Hantar review</button>
                    </form>
                </section>
            @endif
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
                            <p class="text-xs text-emerald-700">✓ Dibayar {{ $payment->paid_at->translatedFormat('j M Y') }} · {{ $payment->gateway_reference }}</p>
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
</x-layouts.customer>
