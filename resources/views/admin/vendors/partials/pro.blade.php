{{-- Neekah Pro for this vendor: whether it is running, what was paid, and a
     way to record a payment that did not go through the checkout. --}}
<section class="mt-8 flex flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-6">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h2 class="flex items-center gap-2 font-display text-xl font-semibold">Neekah Pro @if ($vendor->isPro())<x-vendors.pro-badge />@endif</h2>
        <p class="text-sm text-ink-muted">
            @if ($vendor->isPro())
                {{ __('pages.pro.active_until', ['date' => $vendor->pro_until->translatedFormat('j M Y')]) }}
            @elseif ($vendor->pro_until)
                {{ __('pages.pro.expired_on', ['date' => $vendor->pro_until->translatedFormat('j M Y')]) }}
            @else
                {{ __('pages.pro.never') }}
            @endif
        </p>
    </div>

    @if ($subscriptions->isNotEmpty())
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-ink-muted uppercase">
                    <tr>
                        <th class="py-2 pr-4 font-semibold">{{ __('pages.pro.reference') }}</th>
                        <th class="py-2 pr-4 font-semibold">{{ __('pages.pro.plan') }}</th>
                        <th class="py-2 pr-4 font-semibold">{{ __('pages.pro.amount') }}</th>
                        <th class="py-2 pr-4 font-semibold">{{ __('pages.pro.status') }}</th>
                        <th class="py-2 pr-4 font-semibold">{{ __('pages.pro.period') }}</th>
                        <th class="py-2 font-semibold">{{ __('pages.pro.source') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-line">
                    @foreach ($subscriptions as $subscription)
                        <tr>
                            <td class="py-2 pr-4 font-mono text-xs">{{ $subscription->reference }}</td>
                            <td class="py-2 pr-4">{{ $subscription->plan->label() }}</td>
                            <td class="py-2 pr-4">RM{{ number_format((float) $subscription->amount, 2) }}</td>
                            <td class="py-2 pr-4">{{ $subscription->status->label() }}</td>
                            <td class="py-2 pr-4 whitespace-nowrap">{{ $subscription->starts_at?->translatedFormat('j M Y') }}@if ($subscription->ends_at) – {{ $subscription->ends_at->translatedFormat('j M Y') }}@endif</td>
                            <td class="py-2 text-ink-muted">{{ $subscription->gateway === \App\Models\VendorSubscription::GATEWAY_MANUAL ? __('pages.pro.manual_by', ['name' => $subscription->addedBy?->name ?? '—']) : 'Herepay' }}@if ($subscription->note) · {{ $subscription->note }}@endif</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.vendors.pro', $vendor) }}" class="grid gap-4 border-t border-line pt-5 sm:grid-cols-[1fr_1fr_2fr_auto] sm:items-end">
        @csrf
        <x-form.select :label="__('pages.pro.plan')" name="plan" required>
            @foreach (\App\Enums\VendorPlan::cases() as $plan)
                <option value="{{ $plan->value }}">{{ $plan->label() }} · RM{{ number_format($plan->price()) }}</option>
            @endforeach
        </x-form.select>
        <x-form.field :label="__('pages.pro.amount_paid')" name="amount" type="number" step="0.01" min="0" :placeholder="__('pages.pro.amount_default')" />
        <x-form.field :label="__('pages.pro.note')" name="note" :placeholder="__('pages.pro.note_placeholder')" />
        <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.pro.record_manual') }}</button>
    </form>
</section>
