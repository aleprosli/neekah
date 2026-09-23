<x-layouts.vendor :title="__('pages.pro.title')" :heading="__('pages.pro.done_heading')">
    <div class="flex max-w-xl flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
        @if ($subscription?->isPaid())
            <p class="font-semibold">{{ __('pages.pro.done_paid', ['date' => $vendor->pro_until->translatedFormat('j F Y')]) }}</p>
        @elseif ($subscription?->status === \App\Enums\SubscriptionStatus::Failed)
            <p class="font-semibold">{{ __('pages.pro.done_failed') }}</p>
        @else
            {{-- The gateway's callback can land a moment after the vendor does. --}}
            <p class="font-semibold">{{ __('pages.pro.done_pending') }}</p>
        @endif
        <a href="{{ route('vendor.pro.index') }}" class="self-start rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.pro.back') }}</a>
    </div>
</x-layouts.vendor>
