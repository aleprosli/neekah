@php use App\Enums\PriceUnit; use App\Enums\VendorTier; @endphp

<x-layouts.app :title="$vendor->name" :description="$vendor->tagline">
    <x-site.header />

    <main class="mx-auto max-w-6xl px-4 pt-24 pb-28 sm:px-6 lg:px-10 lg:pt-28 lg:pb-16">
        @if (session('status'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-100">
                {{ session('status') }}
            </div>
        @endif

        {{-- Title (desktop) --}}
        <div class="hidden md:block">
            <p class="text-[11px] font-semibold tracking-wide text-brand-600 uppercase">{{ $category->name }} · {{ $vendor->city }}, {{ $vendor->state }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight lg:text-4xl">{{ $vendor->name }}</h1>
        </div>

        {{-- Photo grid --}}
        <div class="-mx-4 mt-3 sm:mx-0 md:mt-5">
            <div class="grid h-72 grid-cols-4 grid-rows-2 gap-2 sm:overflow-hidden sm:rounded-2xl md:h-[420px]">
                @php $gallery = $vendor->portfolioItems->take(5); @endphp
                @if ($gallery->isNotEmpty())
                    @foreach ($gallery as $item)
                        <div @class(['overflow-hidden', 'col-span-4 row-span-2 md:col-span-2' => $loop->first, 'hidden md:block' => ! $loop->first])>
                            <img src="{{ $item->url() }}" alt="{{ $item->caption }}" class="size-full object-cover">
                        </div>
                    @endforeach
                @else
                    <div class="col-span-4 row-span-2 bg-linear-to-br md:col-span-2 {{ $vendor->cover_tone }}"></div>
                    <div class="hidden bg-linear-to-br opacity-80 md:block {{ $vendor->cover_tone }}"></div>
                    <div class="hidden bg-linear-to-tr opacity-60 md:block {{ $vendor->cover_tone }}"></div>
                    <div class="hidden bg-linear-to-tl opacity-70 md:block {{ $vendor->cover_tone }}"></div>
                    <div class="hidden bg-linear-to-bl opacity-50 md:block {{ $vendor->cover_tone }}"></div>
                @endif
            </div>
        </div>

        <div class="mt-6 grid gap-10 lg:grid-cols-[1fr_360px] lg:gap-16">
            <div class="flex flex-col divide-y divide-line">
                {{-- Summary --}}
                <div class="flex flex-col gap-2 pb-6">
                    <h1 class="font-display text-2xl font-semibold tracking-tight md:hidden">{{ $vendor->name }}</h1>
                    <h2 class="hidden text-lg font-medium md:block">{{ $category->icon }} {{ $category->name }} · {{ $vendor->tagline }}</h2>
                    <p class="text-ink-muted md:hidden">{{ $category->icon }} {{ $category->name }} · {{ $vendor->city }}, {{ $vendor->state }}</p>
                    <p class="text-sm">
                        <span class="font-semibold"><span class="text-gold-500">★</span> {{ $vendor->reviews_count ? number_format($vendor->rating_avg, 1) : 'Baru' }}</span>
                        <span class="text-ink-muted">·</span>
                        <a href="#review" class="underline underline-offset-4">{{ $vendor->reviews_count }} review</a>
                        <span class="text-ink-muted">·</span>
                        {{ $vendor->completed_bookings_count }} majlis selesai
                    </p>
                </div>

                {{-- Vendor identity --}}
                <div class="flex items-center gap-4 py-6">
                    <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-linear-to-br text-lg font-semibold text-white {{ $vendor->cover_tone }}">{{ mb_substr($vendor->name, 0, 1) }}</span>
                    <div>
                        <p class="font-semibold">Dikendalikan oleh {{ $vendor->name }}</p>
                        <p class="text-sm text-ink-muted">@if ($vendor->tier === VendorTier::Recommended)🏆 @endif{{ $vendor->tier->label() }} Vendor · Response rate {{ $vendor->response_rate }}%</p>
                    </div>
                </div>

                {{-- Highlights --}}
                <ul class="flex flex-col gap-5 py-6">
                    @foreach ([['🔒', 'Booking & bayaran melalui Neekah', 'Deposit dan baki direkod dalam platform. Anda dilindungi jika berlaku pertikaian.'], ['⚡', 'Response rate '.$vendor->response_rate.'%', 'Vendor ini membalas kebanyakan enquiry dalam masa 24 jam.'], ['✓', $vendor->completed_bookings_count.' majlis selesai', 'Review di bawah datang daripada pasangan yang benar-benar menempah.']] as [$icon, $title, $text])
                        <li class="flex gap-4">
                            <span class="w-6 shrink-0 text-center text-xl leading-6">{{ $icon }}</span>
                            <div>
                                <p class="font-medium">{{ $title }}</p>
                                <p class="text-sm text-ink-muted">{{ $text }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Description --}}
                <div class="py-6">
                    <p class="leading-relaxed text-ink">{{ $vendor->description }}</p>
                </div>

                {{-- Packages --}}
                <section id="pakej" class="flex flex-col gap-4 py-6">
                    <h2 class="font-display text-2xl font-semibold">Pakej yang ditawarkan</h2>
                    <div class="grid gap-4 sm:grid-cols-2">
                        @forelse ($vendor->packages as $package)
                            <article class="flex flex-col gap-3 rounded-2xl border border-line p-5 transition hover:border-brand-300">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <h3 class="font-semibold">{{ $package->name }}</h3>
                                        <p class="text-sm text-ink-muted">{{ $package->duration }}</p>
                                    </div>
                                    <p class="text-right font-semibold">RM{{ number_format($package->price) }}@if ($vendor->price_unit === PriceUnit::Pax)<span class="block text-xs font-normal text-ink-muted">/ pax</span>@endif</p>
                                </div>
                                @if ($package->description)
                                    <p class="text-sm text-ink-muted">{{ $package->description }}</p>
                                @endif
                                <ul class="flex flex-col gap-1.5 text-sm text-ink-muted">
                                    @foreach ($package->features as $feature)
                                        <li class="flex gap-2"><span class="text-brand-600">✓</span>{{ $feature }}</li>
                                    @endforeach
                                </ul>
                            </article>
                        @empty
                            <p class="text-sm text-ink-muted">Vendor ini belum menambah pakej.</p>
                        @endforelse
                    </div>
                </section>

                {{-- Reviews --}}
                <section id="review" class="flex flex-col gap-6 py-6">
                    <h2 class="font-display text-2xl font-semibold"><span class="text-gold-500">★</span> {{ $vendor->reviews_count ? number_format($vendor->rating_avg, 1) : 'Baru' }} · {{ $vendor->reviews_count }} review</h2>
                    @if ($vendor->reviews->isEmpty())
                        <p class="text-sm text-ink-muted">Belum ada review. Review hanya boleh diberi selepas booking selesai.</p>
                    @else
                        <ul class="grid gap-8 sm:grid-cols-2">
                            @foreach ($vendor->reviews as $review)
                                <li class="flex flex-col gap-2">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-10 items-center justify-center rounded-full bg-surface-muted text-sm font-semibold">{{ mb_substr($review->user->name, 0, 1) }}</span>
                                        <div>
                                            <p class="text-sm font-semibold">{{ $review->user->name }}</p>
                                            <p class="text-xs text-ink-muted">{{ $review->created_at->translatedFormat('F Y') }} · ✓ Verified booking</p>
                                        </div>
                                    </div>
                                    <p class="text-xs text-gold-500">{{ str_repeat('★', $review->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                                    <p class="text-sm leading-relaxed">{{ $review->comment }}</p>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </section>
            </div>

            {{-- Booking card --}}
            <aside id="tempah" class="lg:sticky lg:top-28 lg:self-start">
                <form method="POST" action="{{ route('vendors.bookings.store', $vendor) }}" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6 shadow-xl shadow-brand-900/10">
                    @csrf
                    <p class="text-sm text-ink-muted">Dari <span class="font-display text-2xl font-semibold text-ink">RM{{ number_format($vendor->price_from) }}</span> / {{ $vendor->price_unit->label() }}</p>

                    @if ($errors->any())
                        <ul class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs text-brand-800 dark:bg-brand-900/40 dark:text-brand-100">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="overflow-hidden rounded-xl border border-line">
                        <label class="flex flex-col gap-0.5 border-b border-line px-4 py-2.5 focus-within:bg-surface-muted">
                            <span class="text-[10px] font-semibold tracking-wide uppercase">Tarikh majlis</span>
                            <input type="date" name="event_date" value="{{ old('event_date', $defaultEventDate ?? '') }}" min="{{ now()->addDay()->toDateString() }}" required class="bg-transparent text-sm focus:outline-none">
                        </label>
                        <label class="flex flex-col gap-0.5 border-b border-line px-4 py-2.5 focus-within:bg-surface-muted">
                            <span class="text-[10px] font-semibold tracking-wide uppercase">Pakej</span>
                            <select name="package_id" class="bg-transparent text-sm focus:outline-none" required>
                                @foreach ($vendor->packages as $package)
                                    <option value="{{ $package->id }}" @selected((int) old('package_id') === $package->id)>{{ $package->name }} · RM{{ number_format($package->price) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="flex flex-col gap-0.5 px-4 py-2.5 focus-within:bg-surface-muted">
                            <span class="text-[10px] font-semibold tracking-wide uppercase">Nota untuk vendor</span>
                            <input type="text" name="notes" value="{{ old('notes') }}" placeholder="Contoh: majlis di dewan, 500 tetamu" class="bg-transparent text-sm focus:outline-none">
                        </label>
                    </div>

                    <button type="submit" class="rounded-full bg-brand-600 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" @disabled($vendor->packages->isEmpty())>
                        {{ auth()->check() ? 'Tempah sekarang' : 'Log masuk untuk tempah' }}
                    </button>
                    <p class="text-center text-sm text-ink-muted">Anda belum dicaj lagi. Deposit dibayar selepas booking dibuat.</p>

                    <dl class="flex flex-col gap-2 text-sm">
                        <div class="flex justify-between"><dt class="underline underline-offset-4">Deposit semasa tempah</dt><dd>40%</dd></div>
                        <div class="flex justify-between"><dt class="underline underline-offset-4">Baki sebelum majlis</dt><dd>60%</dd></div>
                        <div class="flex justify-between border-t border-line pt-3 font-semibold"><dt>Status selepas deposit</dt><dd class="text-emerald-600 dark:text-emerald-400">Confirmed</dd></div>
                    </dl>
                </form>
            </aside>
        </div>

        @if ($related->isNotEmpty())
            <section class="mt-12 flex flex-col gap-5 border-t border-line pt-10">
                <h2 class="font-display text-2xl font-semibold">{{ $category->name }} lain yang mungkin anda suka</h2>
                <ul class="grid grid-cols-2 gap-x-3 gap-y-8 sm:gap-x-6 md:grid-cols-3">
                    @foreach ($related as $candidate)
                        <li><x-vendor-card :vendor="$candidate" /></li>
                    @endforeach
                </ul>
            </section>
        @endif
    </main>

    {{-- Mobile sticky booking bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm">
                <p><span class="font-semibold">RM{{ number_format($vendor->price_from) }}</span> <span class="text-ink-muted">/ {{ $vendor->price_unit->label() }}</span></p>
                <p class="text-xs"><span class="text-gold-500">★</span> {{ $vendor->reviews_count ? number_format($vendor->rating_avg, 1) : 'Baru' }} <span class="text-ink-muted">· {{ $vendor->reviews_count }} review</span></p>
            </div>
            <a href="#tempah" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white">Tempah</a>
        </div>
    </div>

    <x-site.footer />
</x-layouts.app>
