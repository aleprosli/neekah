@php use App\Enums\PaymentType; @endphp

<x-layouts.admin :title="'Booking '.$booking->reference" :heading="$booking->reference" :subheading="$booking->event_date->translatedFormat('l, j F Y')">
    <x-slot:actions>
        <x-booking-status :status="$booking->status" class="self-center" />
    </x-slot:actions>

    <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
        <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2">
            <div><dt class="text-ink-muted">Vendor</dt><dd class="font-semibold"><a href="{{ route('admin.vendors.show', $booking->vendor) }}" class="hover:text-brand-700">{{ $booking->vendor->name }}</a></dd><dd class="text-ink-muted">{{ $booking->vendor->category->name }}</dd></div>
            <div><dt class="text-ink-muted">Pengantin</dt><dd class="font-semibold">{{ $booking->user->name }}</dd><dd class="break-words text-ink-muted">{{ $booking->user->email }}</dd></div>
            <div><dt class="text-ink-muted">Pakej</dt><dd class="font-semibold">{{ $booking->package_name }}</dd></div>
            <div><dt class="text-ink-muted">Dibuat</dt><dd class="font-semibold">{{ $booking->created_at->translatedFormat('j M Y, g:i A') }}</dd></div>
            @if ($booking->wedding)
                <div><dt class="text-ink-muted">Majlis</dt><dd class="font-semibold">{{ $booking->wedding->title }}</dd></div>
            @endif
            @if ($booking->notes)
                <div class="sm:col-span-2"><dt class="text-ink-muted">Nota</dt><dd>{{ $booking->notes }}</dd></div>
            @endif
            @if ($booking->review)
                <div class="sm:col-span-2"><dt class="text-ink-muted">Review</dt><dd class="text-gold-500">{{ str_repeat('★', $booking->review->rating) }}</dd><dd>{{ $booking->review->comment }}</dd></div>
            @endif
        </dl>

        <aside class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">Kewangan</p>
            <p class="font-display text-2xl font-semibold">RM{{ number_format((float) $booking->total_amount, 2) }}</p>
            <ul class="flex flex-col gap-2 text-sm">
                @foreach ($booking->payments->sortBy(fn ($payment) => $payment->type === PaymentType::Deposit ? 0 : 1) as $payment)
                    <li class="flex items-center justify-between rounded-xl border border-line px-3 py-2">
                        <span>{{ $payment->type->label() }}<span class="block text-xs text-ink-muted">{{ $payment->reference }}</span></span>
                        <span class="text-right">
                            <span class="font-medium">RM{{ number_format((float) $payment->amount, 2) }}</span>
                            <span @class(['block text-xs', 'text-emerald-600' => $payment->isPaid(), 'text-ink-muted' => ! $payment->isPaid()])>{{ $payment->status->label() }}</span>
                        </span>
                    </li>
                @endforeach
            </ul>
            <dl class="flex flex-col gap-1 border-t border-line pt-3 text-sm">
                <div class="flex justify-between"><dt class="text-ink-muted">Dibayar</dt><dd>RM{{ number_format($booking->paidAmount(), 2) }}</dd></div>
                <div class="flex justify-between"><dt class="text-ink-muted">Komisen ({{ number_format((float) $booking->commission_rate, 0) }}%)</dt><dd>RM{{ number_format((float) $booking->commission_amount, 2) }}</dd></div>
                <div class="flex justify-between font-semibold"><dt>Payout vendor</dt><dd>RM{{ number_format((float) $booking->total_amount - (float) $booking->commission_amount, 2) }}</dd></div>
            </dl>
        </aside>
    </div>
</x-layouts.admin>
