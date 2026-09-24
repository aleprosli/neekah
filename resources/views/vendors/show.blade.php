@php use App\Enums\PriceUnit; use App\Enums\VendorTier; @endphp

<x-layouts.app :title="$vendor->name" :description="$vendor->tagline">
    <x-site.header />

    {{-- On a phone the photo is the first thing on the page, so it sits just
         under the floating header rather than a heading's worth below it. --}}
    <main class="relative bg-ivory">
        <x-site.florals corners="right" sprig />

        <div class="relative mx-auto max-w-6xl px-4 pt-[4.75rem] pb-28 sm:px-6 md:pt-24 lg:px-10 lg:pt-28 lg:pb-16">
        @if (session('status'))
            <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">
                {{ session('status') }}
            </div>
        @endif

        {{-- Title (desktop) --}}
        <div class="hidden md:block">
            <p class="text-[11px] font-semibold tracking-wide text-brand-600 uppercase">{{ $category->name }} · {{ $vendor->city }}, {{ $vendor->state }}</p>
            <h1 class="mt-1 font-display text-3xl font-semibold tracking-tight lg:text-4xl">{{ $vendor->name }}</h1>
        </div>

        {{-- Photo grid and lightbox, mounted from resources/js/components/public/PortfolioGallery.vue --}}
        <div class="-mx-4 mt-3 sm:mx-0 md:mt-5">
            <div
                data-vue="portfolio-gallery"
                data-props="@vueProps([
                    'photos' => $gallery,
                    'vendorName' => $vendor->name,
                    'tone' => $vendor->cover_tone,
                ])"
            >
                {{-- Rendered server-side too, so the first photo is in the markup Google and a link preview read. --}}
                <div class="grid h-[26rem] grid-cols-4 grid-rows-2 gap-2 sm:overflow-hidden sm:rounded-2xl md:h-[420px]">
                    @forelse ($gallery->take(5) as $index => $item)
                        <div @class(['overflow-hidden', 'col-span-4 row-span-2 md:col-span-2' => $index === 0, 'hidden md:block' => $index !== 0])>
                            <img src="{{ $index === 0 ? $item['url'] : $item['thumbnail'] }}" alt="{{ $item['caption'] ?: $vendor->name }}" @if ($index === 0) fetchpriority="high" @else loading="lazy" @endif decoding="async" class="size-full object-cover">
                        </div>
                    @empty
                        <div class="col-span-4 row-span-2 bg-linear-to-br md:col-span-2 {{ $vendor->cover_tone }}"></div>
                        <div class="hidden bg-linear-to-br opacity-80 md:block {{ $vendor->cover_tone }}"></div>
                        <div class="hidden bg-linear-to-tr opacity-60 md:block {{ $vendor->cover_tone }}"></div>
                        <div class="hidden bg-linear-to-tl opacity-70 md:block {{ $vendor->cover_tone }}"></div>
                        <div class="hidden bg-linear-to-bl opacity-50 md:block {{ $vendor->cover_tone }}"></div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-10 break-words lg:grid-cols-[minmax(0,1fr)_360px] lg:gap-16">
            <div class="flex min-w-0 flex-col divide-y divide-line">
                {{-- Summary --}}
                <div class="flex flex-col gap-2 pb-6">
                    <h1 class="font-display text-2xl font-semibold tracking-tight md:hidden">{{ $vendor->name }}</h1>
                    <h2 class="hidden text-lg font-medium md:block"><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" :category="$category" /> {{ $category->name }} · {{ $vendor->tagline }}</h2>
                    <p class="text-ink-muted md:hidden"><x-category-icon class="inline-block size-5 shrink-0 align-[-0.3em]" :category="$category" /> {{ $category->name }} · {{ $vendor->city }}, {{ $vendor->state }}</p>
                    <p class="text-sm">
                        <span class="font-semibold"><span class="text-gold-500">★</span> {{ $vendor->reviews_count ? number_format($vendor->rating_avg, 1) : 'Baru' }}</span>
                        <span class="text-ink-muted">·</span>
                        <a href="#review" class="underline underline-offset-4">{{ $publishedReviewsCount }} review</a>
                        <span class="text-ink-muted">·</span>
                        <span class="text-ink-muted"><x-state-flag :state="$vendor->state" /> {{ $vendor->city }}, {{ $vendor->state }}</span>
                    </p>
                </div>

                {{-- Vendor identity --}}
                <div class="flex items-center gap-4 py-6">
                    <x-vendor-ranked-avatar :vendor="$vendor" />
                    <div class="min-w-0">
                        <p class="font-semibold">{{ __('pages.profile.run_by', ['name' => $vendor->name]) }}</p>
                        <p class="text-sm text-ink-muted">@if ($vendor->tier === VendorTier::Recommended)🏆 @endif{{ $vendor->tier->label() }} Vendor · Response rate {{ $vendor->responseRateLabel() }}</p>
                    </div>
                </div>

                {{-- What they do and where they go. A vendor is rarely one
                     category and rarely one negeri, and a couple planning in
                     Melaka needs to know a KL studio travels. --}}
                <div class="flex flex-col gap-4 py-6">
                    @if ($extraCategories->isNotEmpty())
                        <div class="flex flex-col gap-2">
                            <p class="text-sm font-medium">{{ __('pages.profile.juga_menawarkan') }}</p>
                            <ul class="flex flex-wrap gap-2 text-xs font-semibold">
                                @foreach ($extraCategories as $extra)
                                    <li><a href="{{ route('vendors.index', ['category' => $extra->slug]) }}" class="block rounded-full border border-line px-3.5 py-2 transition hover:border-brand-400"><x-category-icon class="inline-block size-4 shrink-0 align-[-0.25em]" :category="$extra" /> {{ $extra->name }}</a></li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="flex flex-col gap-2">
                        <p class="text-sm font-medium">{{ __('pages.profile.kawasan_perkhidmatan') }}</p>
                        <ul class="flex flex-wrap gap-2 text-xs font-semibold">
                            @foreach ($vendor->serviceStates() as $serviceState)
                                <li><a href="{{ route('vendors.index', ['state' => $serviceState]) }}" @class(['flex items-center gap-2 rounded-full border px-3.5 py-2 transition hover:border-brand-400', 'border-brand-300 bg-brand-50 text-brand-700' => $serviceState === $vendor->state, 'border-line' => $serviceState !== $vendor->state])><x-state-flag :state="$serviceState" /> {{ $serviceState }}</a></li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                @if ($socialLinks = $vendor->socialLinks())
                    <div class="flex flex-col gap-2 py-6">
                        <p class="text-sm font-medium">Ikuti {{ $vendor->name }}</p>
                        <ul class="flex flex-wrap gap-2 text-xs font-semibold">
                            @foreach ($socialLinks as $link)
                                <li><a href="{{ $link['url'] }}" target="_blank" rel="noopener nofollow ugc" class="block rounded-full border border-line px-3.5 py-2 transition hover:border-brand-400">{{ $link['label'] }} ↗</a></li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <x-vendors.share :vendor="$vendor" class="py-6" />

                {{-- Highlights --}}
                <ul class="flex flex-col gap-5 py-6">
                    @foreach ([['💬', __('pages.profile.highlight_contact'), __('pages.profile.highlight_contact_detail')], ['⚡', __('pages.profile.highlight_response', ['rate' => $vendor->responseRateLabel()]), $vendor->response_rate === null ? __('pages.profile.highlight_response_none') : __('pages.profile.highlight_response_detail')], ['★', __('pages.profile.highlight_reviews', ['count' => $publishedReviewsCount]), __('pages.profile.highlight_reviews_detail')]] as [$icon, $title, $text])
                        <li class="flex gap-4">
                            <span class="w-6 shrink-0 text-center text-xl leading-6">{{ $icon }}</span>
                            <div class="min-w-0">
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
                    <h2 class="font-display text-2xl font-semibold">{{ __('pages.profile.pakej_yang_ditawarkan') }}</h2>
                    <div class="grid gap-4 sm:grid-cols-2 [&>article]:min-w-0">
                        @forelse ($vendor->packages as $package)
                            <article class="flex flex-col overflow-hidden rounded-2xl border border-line transition hover:border-brand-300">
                                @if ($package->imageUrl())
                                    <img src="{{ $package->imageUrl() }}" alt="{{ $package->name }}" loading="lazy" class="aspect-[4/3] w-full object-cover">
                                @endif
                                <div class="flex flex-1 flex-col gap-3 p-5">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
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
                                            <li class="flex gap-2"><span class="shrink-0 text-brand-600">✓</span><span class="min-w-0">{{ $feature }}</span></li>
                                        @endforeach
                                    </ul>
                                </div>
                            </article>
                        @empty
                            <p class="text-sm text-ink-muted">{{ __('pages.profile.vendor_ini_belum_menambah_pakej') }}</p>
                        @endforelse
                    </div>
                </section>

                {{-- Reviews --}}
                <section id="review" class="flex flex-col gap-6 py-6">
                    <div class="flex flex-col gap-2">
                        <h2 class="font-display text-2xl font-semibold">{{ __('pages.profile.review') }}</h2>
                        {{-- Two numbers, never merged. Only the booking-backed
                             ones move the vendor's rating and ranking, so a page
                             that showed one combined average would be claiming
                             something the platform cannot stand behind. --}}
                        <div class="flex flex-wrap gap-x-6 gap-y-1 text-sm">
                            @if ($vendor->reviews_count)
                                <p><span class="text-gold-500">★</span> <span class="font-semibold">{{ number_format($vendor->rating_avg, 1) }}</span> <span class="text-ink-muted">· {{ $vendor->reviews_count }} dari tempahan disahkan</span></p>
                            @endif
                            @if ($openReviews['total'])
                                <p><span class="text-gold-500">★</span> <span class="font-semibold">{{ number_format($openReviews['average'], 1) }}</span> <span class="text-ink-muted">· {{ $openReviews['total'] }} review terbuka</span></p>
                            @endif
                        </div>
                    </div>

                    @if ($vendor->reviews->isEmpty())
                        <p class="text-sm text-ink-muted">{{ __('pages.profile.no_reviews', ['name' => $vendor->name]) }}</p>
                    @else
                        <ul class="grid gap-8 sm:grid-cols-2 [&>li]:min-w-0">
                            @foreach ($vendor->reviews as $review)
                                <li class="flex min-w-0 flex-col gap-2 break-words">
                                    <div class="flex items-center gap-3">
                                        <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-surface-muted text-sm font-semibold">{{ $review->authorInitial() }}</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold">{{ $review->authorName() }}</p>
                                            <p class="text-xs text-ink-muted">
                                                {{ $review->created_at->translatedFormat('F Y') }} ·
                                                <span @class(['font-medium text-emerald-700' => $review->isVerified()])>{{ $review->sourceLabel() }}</span>
                                            </p>
                                        </div>
                                    </div>

                                    <p class="text-xs text-gold-500">{{ str_repeat('★', $review->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                                    <p class="text-sm leading-relaxed">{{ $review->comment }}</p>

                                    @if ($review->photos->isNotEmpty())
                                        <ul class="flex flex-wrap gap-2">
                                            @foreach ($review->photos as $photo)
                                                <li>
                                                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                                        <img src="{{ $photo->thumbnailUrl() }}" alt="Gambar review daripada {{ $review->authorName() }}" loading="lazy" class="size-20 rounded-lg object-cover">
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    @if ($review->hasReply())
                                        <div class="mt-1 min-w-0 rounded-xl border-l-2 border-brand-200 bg-surface-muted/60 px-3 py-2">
                                            <p class="text-xs font-semibold">Jawapan {{ $vendor->name }}</p>
                                            <p class="mt-1 text-sm leading-relaxed">{{ $review->reply }}</p>
                                        </div>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <x-vendors.review-form :vendor="$vendor" />
                </section>
            </div>

            {{-- What the couple does next. Booking through Neekah is off while
                 the platform is a network: they deal with the vendor themselves,
                 so this offers WhatsApp and an enquiry. Switching
                 config('neekah.bookings_enabled') on brings the booking form back
                 for when booking is automated. --}}
            <aside id="hubungi" class="min-w-0 lg:sticky lg:top-28 lg:self-start">
                @if (config('neekah.bookings_enabled'))
                    <form method="POST" action="{{ route('vendors.bookings.store', $vendor) }}" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6 shadow-xl shadow-brand-900/10">
                        @csrf
                        <p class="text-sm text-ink-muted">{{ __('pages.profile.dari') }}<span class="font-display text-2xl font-semibold text-ink">RM{{ number_format($vendor->price_from) }}</span> / {{ $vendor->price_unit->label() }}</p>

                        @if ($errors->any())
                            <ul class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="overflow-hidden rounded-xl border border-line">
                            <label class="flex flex-col gap-0.5 border-b border-line px-4 py-2.5 focus-within:bg-surface-muted">
                                <span class="text-[10px] font-semibold tracking-wide uppercase">{{ __('pages.profile.tarikh_majlis') }}</span>
                                <input type="date" name="event_date" value="{{ old('event_date', $defaultEventDate ?? '') }}" min="{{ now()->addDay()->toDateString() }}" required class="bg-transparent text-sm focus:outline-none">
                            </label>
                            <label class="flex flex-col gap-0.5 border-b border-line px-4 py-2.5 focus-within:bg-surface-muted">
                                <span class="text-[10px] font-semibold tracking-wide uppercase">{{ __('pages.profile.pakej') }}</span>
                                <select name="package_id" class="nk-select w-full bg-transparent pr-6 text-sm focus:outline-none" required>
                                    @foreach ($vendor->packages as $package)
                                        <option value="{{ $package->id }}" @selected((int) old('package_id') === $package->id)>{{ $package->name }} · RM{{ number_format($package->price) }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="flex flex-col gap-0.5 px-4 py-2.5 focus-within:bg-surface-muted">
                                <span class="text-[10px] font-semibold tracking-wide uppercase">{{ __('pages.profile.nota_untuk_vendor') }}</span>
                                <input type="text" name="notes" value="{{ old('notes') }}" :placeholder="__('pages.profile.contoh_majlis_di_dewan_500')" class="bg-transparent text-sm focus:outline-none">
                            </label>
                        </div>

                        <x-turnstile />

                        <button type="submit" class="rounded-full bg-brand-600 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" @disabled($vendor->packages->isEmpty())>
                            {{ auth()->check() ? __('pages.profile.book_now') : __('pages.profile.login_to_book') }}
                        </button>

                        @auth
                            {{-- The vendor's own number is only ever rendered for a signed-in
                                 visitor, so it cannot be scraped from the public markup. --}}
                            @if ($whatsapp = $vendor->whatsappUrl('Hai '.$vendor->name.', saya jumpa anda di Neekah. Boleh saya tanya tentang pakej untuk majlis saya?'))
                                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 rounded-full border border-brand-600 py-3.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
                                    <span aria-hidden="true">💬</span>{{ __('pages.profile.whatsapp_vendor') }}</a>
                            @endif

                            @if ($vendor->phone)
                                <p class="text-center text-sm text-ink-muted">{{ __('pages.profile.atau_hubungi_terus_di') }}<a href="tel:{{ preg_replace('/[^0-9+]/', '', $vendor->phone) }}" class="font-medium text-ink underline underline-offset-4">{{ $vendor->phone }}</a>
                                </p>
                            @endif
                        @else
                            @if ($vendor->phone || $vendor->whatsapp)
                                <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 rounded-full border border-brand-600 py-3.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">
                                    <span aria-hidden="true">💬</span>{{ __('pages.profile.log_masuk_untuk_whatsapp_vendor') }}</a>
                                <p class="text-center text-sm text-ink-muted">{{ __('pages.profile.nombor_vendor_hanya_dipaparkan_kepada') }}</p>
                            @endif
                        @endauth

                        <p class="text-center text-sm text-ink-muted">{{ __('pages.profile.anda_tidak_dicaj_di_sini') }}</p>
                    </form>
                @else
                    <div class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6 shadow-xl shadow-brand-900/10">
                        <p class="text-sm text-ink-muted">{{ __('pages.profile.dari_2') }}<span class="font-display text-2xl font-semibold text-ink">RM{{ number_format($vendor->price_from) }}</span> / {{ $vendor->price_unit->label() }}</p>

                        @auth
                            {{-- The vendor's own number is only ever rendered for a signed-in
                                 visitor, so it cannot be scraped from the public markup. --}}
                            @if ($whatsapp = $vendor->whatsappUrl('Hai '.$vendor->name.', saya jumpa anda di Neekah. Boleh saya tanya tentang pakej untuk majlis saya?'))
                                <a href="{{ $whatsapp }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 rounded-full bg-brand-600 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                                    <span aria-hidden="true">💬</span>{{ __('pages.profile.whatsapp_vendor_2') }}</a>
                            @endif

                            @if ($vendor->phone)
                                <p class="text-center text-sm text-ink-muted">{{ __('pages.profile.atau_hubungi_terus_di_2') }}<a href="tel:{{ preg_replace('/[^0-9+]/', '', $vendor->phone) }}" class="font-medium text-ink underline underline-offset-4">{{ $vendor->phone }}</a>
                                </p>
                            @endif
                        @else
                            <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 rounded-full bg-brand-600 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                                <span aria-hidden="true">💬</span>{{ __('pages.profile.log_masuk_untuk_whatsapp_vendor_2') }}</a>
                            <p class="text-center text-sm text-ink-muted">{{ __('pages.profile.nombor_vendor_hanya_dipaparkan_kepada_2') }}</p>
                        @endauth

                        <p class="text-center text-sm text-ink-muted">{{ __('pages.profile.anda_berurusan_terus_dengan_vendor') }}</p>
                    </div>
                @endif

                {{-- Enquiry. Not folded into a <details> any more: with booking
                     off this is the action the page is here for. --}}
                <div class="mt-4 rounded-2xl border border-line bg-surface-raised p-5">
                    <p class="text-sm font-semibold">{{ __('pages.profile.hantar_enquiry') }}</p>
                    <p class="mt-1 text-sm text-ink-muted">{{ __('pages.profile.tanya_tentang_tarikh_pakej_atau') }}</p>
                    @auth
                        <form method="POST" action="{{ route('vendors.enquiries.store', $vendor) }}" class="mt-4 flex flex-col gap-3">
                            @csrf
                            <textarea name="message" rows="4" required :placeholder="__('pages.profile.contoh_masih_ada_slot_untuk')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">{{ old('message') }}</textarea>
                            <input type="date" name="event_date" value="{{ old('event_date', $defaultEventDate ?? '') }}" min="{{ now()->addDay()->toDateString() }}" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                            <button type="submit" class="rounded-full border border-brand-600 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ __('pages.profile.hantar_enquiry_2') }}</button>
                        </form>
                    @else
                        <p class="mt-3 text-sm text-ink-muted"><a href="{{ route('login') }}" class="font-medium text-brand-600 underline underline-offset-4">{{ __('pages.profile.log_masuk') }}</a> {{ __('pages.profile.untuk_menghantar_enquiry') }}</p>
                    @endauth
                </div>

                @auth
                    @if (auth()->user()->isCustomer())
                        <p class="mt-3 text-center text-xs text-ink-muted">{{ __('pages.profile.ada_masalah_dengan_vendor_ini') }}<a href="{{ route('vendors.report.create', $vendor) }}" class="font-medium underline underline-offset-4 hover:text-ink">{{ __('pages.profile.laporkan_vendor') }}</a>
                        </p>
                    @endif
                @endauth
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
        </div>
    </main>

    {{-- Mobile sticky booking bar --}}
    <div class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 px-4 py-3 backdrop-blur lg:hidden">
        <div class="flex items-center justify-between gap-3">
            <div class="text-sm">
                <p><span class="font-semibold">RM{{ number_format($vendor->price_from) }}</span> <span class="text-ink-muted">/ {{ $vendor->price_unit->label() }}</span></p>
                <p class="text-xs"><span class="text-gold-500">★</span> {{ $vendor->reviews_count ? number_format($vendor->rating_avg, 1) : 'Baru' }} <span class="text-ink-muted">· {{ $publishedReviewsCount }} review</span></p>
            </div>
            <a href="#hubungi" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white">{{ __('pages.profile.hubungi_vendor') }}</a>
        </div>
    </div>

    <x-site.footer />
</x-layouts.app>
