<x-layouts.vendor :title="__('pages.pro.title')" :heading="__('pages.pro.heading')" :subheading="__('pages.pro.subheading')">
    <div class="flex flex-col gap-12">
        {{-- Where the vendor stands now. --}}
        <section @class([
            'flex flex-wrap items-center justify-between gap-4 rounded-2xl border p-6',
            'border-gold-300 bg-gold-300/15' => $vendor->isPro(),
            'border-line bg-surface-raised' => ! $vendor->isPro(),
        ])>
            <div>
                <p class="flex items-center gap-2 font-semibold">
                    @if ($vendor->isPro())
                        <x-vendors.pro-badge /> {{ __('pages.pro.active_until', ['date' => $vendor->pro_until->translatedFormat('j F Y')]) }}
                    @elseif ($vendor->pro_until)
                        {{ __('pages.pro.expired_on', ['date' => $vendor->pro_until->translatedFormat('j F Y')]) }}
                    @else
                        {{ __('pages.pro.free_plan') }}
                    @endif
                </p>
                <p class="mt-1 text-sm text-ink-muted">{{ $vendor->isPro() ? __('pages.pro.renew_hint') : __('pages.pro.free_hint') }}</p>
            </div>
        </section>

        {{-- What Pro gives, told as before and after: the scattered notes of a
             vendor's day, then the Pro feature that replaces each one. --}}
        @php
            $benefitKeys = ['enquiries', 'quotations', 'contracts', 'booking', 'boost', 'analytics', 'ranking', 'badge'];
            $benefits = array_map(fn (string $key): array => [
                'key' => $key,
                'title' => __('pages.pro.benefits.'.$key.'.title'),
                'body' => __('pages.pro.benefits.'.$key.'.body'),
                'pain' => __('pages.pro.pains.'.$key),
            ], $benefitKeys);
        @endphp
        {{-- resources/js/components/vendor/VendorProShowcase.vue --}}
        <div data-vue="vendor-pro-showcase" data-props="@vueProps([
            'eyebrow' => __('pages.pro.story.eyebrow'),
            'headline' => __('pages.pro.story.headline'),
            'lead' => __('pages.pro.story.lead'),
            'footnote' => __('pages.pro.story.footnote'),
            'afterHeading' => __('pages.pro.story.after_heading'),
            'afterLead' => __('pages.pro.story.after_lead'),
            'cta' => $vendor->isPro() ? null : ['url' => '#harga', 'label' => __('pages.pro.story.cta')],
            'benefits' => $benefits,
        ])">
            <ul class="grid gap-4 sm:grid-cols-2">
                @foreach ($benefits as $benefit)
                    <li class="rounded-2xl border border-line bg-surface-raised p-5">
                        <p class="font-semibold">{{ $benefit['title'] }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ $benefit['body'] }}</p>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- Paying. The yearly plan says what it saves against twelve months. --}}
        @php
            $monthly = collect($plans)->firstWhere('plan', App\Enums\VendorPlan::Monthly)['price'] ?? null;
        @endphp
        <section id="harga" class="flex scroll-mt-24 flex-col gap-5">
            <div class="text-center">
                <h2 class="font-display text-3xl font-semibold tracking-tight">{{ $vendor->isPro() ? __('pages.pro.renew_heading') : __('pages.pro.upgrade_heading') }}</h2>
                <p class="mt-2 text-sm text-ink-muted">{{ __('pages.pro.price_lead') }}</p>
            </div>

            @error('plan')
                <p class="rounded-xl border border-brand-200 bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ $message }}</p>
            @enderror

            <div class="mx-auto grid w-full max-w-3xl gap-4 sm:grid-cols-2">
                @foreach ($plans as $option)
                    @php
                        $months = $option['plan']->months();
                        $saving = $monthly && $months > 1 ? (int) round((1 - $option['price'] / ($monthly * $months)) * 100) : 0;
                        $featured = $months > 1;
                    @endphp
                    <form method="POST" action="{{ route('vendor.pro.checkout') }}" @class([
                        'relative flex flex-col gap-4 rounded-3xl p-6 sm:p-7',
                        'bg-linear-to-br from-ink via-brand-900 to-ink text-surface ring-1 ring-gold-300/60' => $featured,
                        'bg-surface-raised ring-1 ring-line' => ! $featured,
                    ])>
                        @csrf
                        <input type="hidden" name="plan" value="{{ $option['plan']->value }}">
                        <div class="flex items-center justify-between gap-3">
                            <p @class(['text-sm font-semibold uppercase tracking-wide', 'text-gold-300' => $featured, 'text-ink-muted' => ! $featured])>{{ $option['plan']->label() }}</p>
                            @if ($saving > 0)
                                <span class="rounded-full bg-gold-300 px-3 py-1 text-xs font-bold text-ink">{{ __('pages.pro.save_percent', ['percent' => $saving]) }}</span>
                            @endif
                        </div>
                        <p>
                            <span class="font-display text-4xl font-semibold">RM{{ number_format($option['price']) }}</span>
                            <span @class(['text-sm', 'text-surface/70' => $featured, 'text-ink-muted' => ! $featured])>/ {{ trans_choice('pages.pro.months', $months, ['count' => $months]) }}</span>
                        </p>
                        @if ($months > 1)
                            <p class="text-sm text-surface/70">{{ __('pages.pro.per_month', ['price' => 'RM'.number_format($option['price'] / $months, 2)]) }}</p>
                        @endif
                        @if ($canCheckout)
                            <button type="submit" @class([
                                'mt-auto rounded-full py-3 text-sm font-semibold transition',
                                'bg-gold-300 text-ink hover:bg-gold-400' => $featured,
                                'bg-brand-600 text-white hover:bg-brand-700' => ! $featured,
                            ])>{{ __('pages.pro.pay_fpx') }}</button>
                        @endif
                    </form>
                @endforeach
            </div>

            @unless ($canCheckout)
                <p class="text-center text-sm text-ink-muted">{{ __('pages.pro.checkout_soon') }}</p>
            @endunless

            <p class="mx-auto max-w-3xl text-center text-xs text-ink-muted">{{ __('pages.pro.fair_note') }}</p>
        </section>

        {{-- Analytics: the whole window for Pro, last week's totals otherwise. --}}
        {{-- Pro Elite: what Pro adds for vendors who perform. Earned, never bought. --}}
        @if (app(\App\Support\ProSettings::class)->eliteEnabled())
            <section class="relative overflow-hidden rounded-3xl border border-gold-300/70 bg-linear-to-br from-ink via-brand-900 to-ink p-6 text-surface sm:p-8">
                <div class="pointer-events-none absolute -top-20 -right-16 size-64 rounded-full bg-gold-300/15 blur-3xl" aria-hidden="true"></div>
                <div class="relative flex flex-col gap-4">
                    <div>
                        <p class="text-[11px] font-semibold tracking-[0.3em] text-gold-300 uppercase">✦ Pro Elite</p>
                        <h2 class="mt-1 font-display text-2xl font-semibold">{{ $vendor->isElite() ? __('pages.pro.elite.you_are') : __('pages.pro.elite.title') }}</h2>
                        <p class="mt-1 max-w-2xl text-sm text-surface/75">{{ __('pages.pro.elite.body') }}</p>
                    </div>
                    <ul class="grid gap-3 sm:grid-cols-2">
                        @foreach (['badge', 'row', 'order', 'tokens'] as $perk)
                            <li class="rounded-2xl bg-white/5 p-4 ring-1 ring-white/10">
                                <p class="text-sm font-semibold text-gold-300">{{ __('pages.pro.elite.perks.'.$perk.'.title') }}</p>
                                <p class="mt-1 text-xs text-surface/75">{{ __('pages.pro.elite.perks.'.$perk.'.body', ['count' => app(\App\Support\ProSettings::class)->eliteBonusTokens()]) }}</p>
                            </li>
                        @endforeach
                    </ul>
                    @unless ($vendor->isElite())
                        <p class="text-xs text-surface/75">{{ __('pages.pro.elite.how', ['tier' => $vendor->tier->label()]) }}</p>
                    @endunless
                </div>
            </section>
        @endif

        <section class="flex flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">{{ $vendor->isPro() ? __('pages.pro.analytics_heading_pro') : __('pages.pro.analytics_heading_free') }}</h2>
            <div class="grid gap-4 sm:grid-cols-3">
                <x-stat-card :label="__('pages.pro.stats.profile_views')" :value="number_format($totals['profile_views'])" />
                <x-stat-card :label="__('pages.pro.stats.whatsapp_clicks')" :value="number_format($totals['whatsapp_clicks'])" />
                <x-stat-card :label="__('pages.pro.stats.phone_clicks')" :value="number_format($totals['phone_clicks'])" />
            </div>
            @if ($vendor->isPro())
                <div class="rounded-2xl border border-line bg-surface-raised p-5">
                    <p class="mb-3 text-sm font-medium">{{ __('pages.pro.daily_views') }}</p>
                    <x-chart.bars :series="$daily" />
                </div>
            @else
                <p class="rounded-2xl border border-dashed border-line p-5 text-sm text-ink-muted">{{ __('pages.pro.analytics_teaser') }}</p>
            @endif
        </section>

        @if ($history->isNotEmpty())
            <section class="flex flex-col gap-3">
                <h2 class="font-display text-xl font-semibold">{{ __('pages.pro.history') }}</h2>
                <ul class="divide-y divide-line rounded-2xl border border-line">
                    @foreach ($history as $row)
                        <li class="flex flex-wrap items-center justify-between gap-2 p-4 text-sm">
                            <a href="{{ $row['url'] }}" class="font-mono text-xs hover:text-brand-700">{{ $row['reference'] }}</a>
                            <span>{{ $row['plan'] }} · RM{{ $row['amount'] }}</span>
                            <span class="text-ink-muted">{{ $row['status'] }}@if ($row['until']) · {{ __('pages.pro.until', ['date' => $row['until']]) }}@endif</span>
                            @if ($row['document_url'])
                                <a href="{{ $row['document_url'] }}" target="_blank" rel="noopener" class="text-xs font-medium text-brand-700 underline underline-offset-4">{{ __('pages.payment_page.view_receipt') }}</a>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </div>
</x-layouts.vendor>
