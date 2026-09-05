@php use App\Enums\BookingStatus; use App\Enums\PaymentType; @endphp

<x-layouts.vendor :title="'Booking '.$booking->reference" :heading="$booking->reference" :subheading="$booking->user->name.' · '.$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
        @if ($booking->status === BookingStatus::Confirmed && $booking->event_date->isPast())
            <form method="POST" action="{{ route('vendor.bookings.complete', $booking) }}">
                @csrf
                <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Tandakan selesai</button>
            </form>
        @endif
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <div class="flex flex-col gap-6">
            <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2">
                <div><dt class="text-ink-muted">Pelanggan</dt><dd class="font-semibold">{{ $booking->user->name }}</dd><dd class="break-words text-ink-muted">{{ $booking->user->email }}@if ($booking->user->phone) · {{ $booking->user->phone }}@endif</dd></div>
                <div><dt class="text-ink-muted">Pakej</dt><dd class="font-semibold">{{ $booking->package_name }}</dd></div>
                <div><dt class="text-ink-muted">Tarikh majlis</dt><dd class="font-semibold">{{ $booking->event_date->translatedFormat('l, j F Y') }}</dd></div>
                <div><dt class="text-ink-muted">Dibuat</dt><dd class="font-semibold">{{ $booking->created_at->translatedFormat('j M Y, g:i A') }}</dd></div>
                @if ($booking->notes)
                    <div class="sm:col-span-2"><dt class="text-ink-muted">Nota</dt><dd>{{ $booking->notes }}</dd></div>
                @endif
            </dl>

            @if ($timelineItems->isNotEmpty())
                <section class="rounded-2xl border border-line p-5">
                    <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Slot anda pada hari majlis</p>
                    <ol class="mt-3 flex flex-col gap-3">
                        @foreach ($timelineItems as $item)
                            <li class="flex gap-4 text-sm">
                                <span class="w-24 shrink-0 font-display font-semibold">{{ $item->startsAtLabel() }}</span>
                                <div class="min-w-0">
                                    <p class="font-medium">{{ $item->title }}</p>
                                    @if ($item->location)<p class="text-xs text-ink-muted">📍 {{ $item->location }}</p>@endif
                                    @if ($item->notes)<p class="text-xs text-ink-muted">{{ $item->notes }}</p>@endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                    <p class="mt-3 border-t border-line pt-3 text-xs text-ink-muted">Anda hanya melihat slot yang ditugaskan kepada anda. Pengantin menguruskan timeline penuh.</p>
                </section>
            @endif

            @if ($booking->review)
                <div class="rounded-2xl border border-line p-5">
                    <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Review pelanggan</p>
                    <p class="mt-2 text-gold-500">{{ str_repeat('★', $booking->review->rating) }}</p>
                    <p class="mt-1 text-sm">{{ $booking->review->comment }}</p>
                </div>
            @endif
        </div>

        <aside class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Pembayaran</p>
            <p class="font-display text-2xl font-semibold">RM{{ number_format((float) $booking->total_amount, 2) }}</p>
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($booking->payments->sortBy(fn ($payment) => $payment->type === PaymentType::Deposit ? 0 : 1) as $payment)
                    <li class="flex items-center justify-between rounded-xl border border-line px-3 py-2">
                        <span>{{ $payment->type->label() }}</span>
                        <span class="text-right">
                            <span class="font-medium">RM{{ number_format((float) $payment->amount, 2) }}</span>
                            <span @class(['block text-xs', 'text-emerald-600' => $payment->isPaid(), 'text-ink-muted' => ! $payment->isPaid()])>{{ $payment->status->label() }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            <dl class="flex flex-col gap-1 border-t border-line pt-3 text-sm">
                <div class="flex justify-between text-ink-muted"><dt>Komisen platform ({{ number_format((float) $booking->commission_rate, 0) }}%)</dt><dd>- RM{{ number_format((float) $booking->commission_amount, 2) }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Anda terima</dt><dd>RM{{ number_format((float) $booking->total_amount - (float) $booking->commission_amount, 2) }}</dd></div>
            </dl>
        </aside>
    </div>
</x-layouts.vendor>
