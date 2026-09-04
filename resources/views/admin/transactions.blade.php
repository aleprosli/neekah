@php use App\Enums\PaymentStatus; @endphp

<x-layouts.admin title="Kewangan" heading="Kewangan" subheading="Nilai transaksi kasar, komisen platform dan payout vendor.">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Gross transaction value" :value="'RM'.number_format($gross, 2)" hint="Semua bayaran diterima" />
        <x-stat-card label="Komisen platform" :value="'RM'.number_format($commission, 2)" hint="8% daripada booking aktif" />
        <x-stat-card label="Payout vendor" :value="'RM'.number_format($payout, 2)" hint="Selepas komisen" />
        <x-stat-card label="Belum dibayar" :value="'RM'.number_format($outstanding, 2)" hint="Deposit & baki tertunggak" />
    </div>

    <div class="mt-6 mb-6 flex flex-wrap gap-2">
        <a href="{{ route('admin.transactions.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => ! $status, 'border-line hover:border-brand-400' => $status])>Semua</a>
        @foreach (PaymentStatus::cases() as $case)
            <a href="{{ route('admin.transactions.index', ['status' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium', 'border-brand-600 bg-brand-600 text-white' => $status === $case, 'border-line hover:border-brand-400' => $status !== $case])>{{ $case->label() }}</a>
        @endforeach
    </div>

    @if ($payments->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Tiada transaksi.</p>
    @else
        <div class="overflow-x-auto rounded-2xl border border-line">
            <table class="w-full text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Rujukan</th>
                        <th class="px-4 py-3 font-semibold">Booking</th>
                        <th class="px-4 py-3 font-semibold">Vendor</th>
                        <th class="px-4 py-3 font-semibold">Jenis</th>
                        <th class="px-4 py-3 text-right font-semibold">Amaun</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Tarikh</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($payments as $payment)
                        <tr class="transition hover:bg-surface-muted/60">
                            <td class="px-4 py-3 font-medium whitespace-nowrap">{{ $payment->reference }}</td>
                            <td class="px-4 py-3"><a href="{{ route('admin.bookings.show', $payment->booking) }}" class="hover:text-brand-700">{{ $payment->booking->reference }}</a></td>
                            <td class="px-4 py-3">{{ $payment->booking->vendor->name }}</td>
                            <td class="px-4 py-3">{{ $payment->type->label() }}</td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">RM{{ number_format((float) $payment->amount, 2) }}</td>
                            <td class="px-4 py-3">
                                <span @class(['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $payment->isPaid(), 'bg-amber-100 text-amber-800' => $payment->status === PaymentStatus::Pending, 'bg-surface-muted text-ink-muted' => in_array($payment->status, [PaymentStatus::Failed, PaymentStatus::Refunded], true)])>{{ $payment->status->label() }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-ink-muted">{{ ($payment->paid_at ?? $payment->created_at)->translatedFormat('j M Y') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $payments->links() }}</div>
    @endif
</x-layouts.admin>
