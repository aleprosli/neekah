<div class="sticky top-3 z-40 h-0 px-3 sm:px-6">
    <header class="mx-auto flex max-w-6xl items-center justify-between gap-2 rounded-full sm:gap-4 border border-line/70 bg-surface/85 py-2 pr-2 pl-4 shadow-lg shadow-black/5 backdrop-blur-md">
        {{-- max-w lets the lockup scale down on a very narrow phone instead of
             pushing the log in and register buttons off the header. --}}
        <a href="{{ route('vendors.index') }}" class="flex min-w-0 items-center" aria-label="{{ config('app.name') }}">
            <x-brand.lockup class="h-7 max-w-[42vw] object-contain sm:h-8" />
        </a>

        <nav data-nav-region class="hidden items-center gap-1 text-sm font-medium md:flex" aria-label="Utama">
            <a href="{{ route('vendors.index') }}" @class(['rounded-full px-4 py-2 transition hover:bg-surface-muted', 'bg-surface-muted text-brand-700' => App\Support\Locales::routeIs('vendors.*')])>{{ __('nav.find_vendors') }}</a>
            <a href="{{ route('landing') }}#cara" class="rounded-full px-4 py-2 transition hover:bg-surface-muted">{{ __('nav.how_it_works') }}</a>
            <a href="{{ route('vendor.register') }}" class="rounded-full px-4 py-2 transition hover:bg-surface-muted">{{ __('nav.for_vendors') }}</a>
            <a href="{{ route('blog.index') }}" @class(['rounded-full px-4 py-2 transition hover:bg-surface-muted', 'bg-surface-muted text-brand-700' => App\Support\Locales::routeIs('blog.*')])>{{ __('nav.blog') }}</a>
        </nav>

        <div class="flex shrink-0 items-center gap-1">
            @auth
                <x-notification-bell />
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.dashboard') }}" @class(['hidden rounded-full px-4 py-2 text-sm font-medium transition hover:bg-surface-muted sm:inline', 'bg-surface-muted text-brand-700' => App\Support\Locales::routeIs('admin.*')])>{{ __('nav.admin') }}</a>
                @elseif (auth()->user()->isVendor())
                    <a href="{{ route('vendor.dashboard') }}" @class(['hidden rounded-full px-4 py-2 text-sm font-medium transition hover:bg-surface-muted sm:inline', 'bg-surface-muted text-brand-700' => App\Support\Locales::routeIs('vendor.*')])>{{ __('nav.dashboard') }}</a>
                @else
                    <a href="{{ route('dashboard') }}" @class(['hidden rounded-full px-4 py-2 text-sm font-medium transition hover:bg-surface-muted sm:inline', 'bg-surface-muted text-brand-700' => App\Support\Locales::routeIs('dashboard', 'weddings.*', 'bookings.*', 'enquiries.*')])>{{ __('nav.my_wedding') }}</a>
                @endif
                <details data-popover class="relative">
                    <summary class="flex cursor-pointer list-none items-center gap-2 rounded-full border border-line py-1 pr-1 pl-3 text-sm font-medium select-none hover:shadow-md [&::-webkit-details-marker]:hidden">
                        <span class="hidden max-w-32 truncate sm:inline">{{ auth()->user()->name }}</span>
                        <span class="flex size-7 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                    </summary>
                    <div class="absolute top-full right-0 z-20 mt-2 w-56 rounded-2xl border border-line bg-surface-raised p-2 text-sm shadow-xl shadow-brand-900/10">
                        <p class="truncate px-3 py-2 text-xs text-ink-muted">{{ auth()->user()->email }}</p>
                        @if (auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" class="block rounded-xl px-3 py-2 hover:bg-surface-muted">Admin panel</a>
                        @elseif (auth()->user()->isVendor())
                            <a href="{{ route('vendor.dashboard') }}" class="block rounded-xl px-3 py-2 hover:bg-surface-muted">Dashboard vendor</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2 hover:bg-surface-muted">{{ __('nav.my_wedding') }}</a>
                            <a href="{{ route('bookings.index') }}" class="block rounded-xl px-3 py-2 hover:bg-surface-muted">Tempahan saya</a>
                            <a href="{{ route('enquiries.index') }}" class="block rounded-xl px-3 py-2 hover:bg-surface-muted">Enquiry</a>
                        @endif
                        <a href="{{ route('account.edit') }}" class="block rounded-xl px-3 py-2 hover:bg-surface-muted">{{ __('nav.account') }}</a>

                        <div class="flex items-center justify-between gap-2 rounded-xl px-3 py-2">
                            <span class="text-xs text-ink-muted">{{ __('nav.language') }}</span>
                            <x-site.language-switcher />
                        </div>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full rounded-xl px-3 py-2 text-left hover:bg-surface-muted">Log keluar</button>
                        </form>
                    </div>
                </details>
            @else
                <x-site.language-switcher class="mr-1" />
                <a href="{{ route('login') }}" class="rounded-full px-3 py-2 text-sm font-medium whitespace-nowrap transition hover:bg-surface-muted sm:px-4">{{ __('nav.login') }}</a>
                <a href="{{ route('register') }}" class="rounded-full bg-brand-600 px-3 py-2 text-sm font-semibold whitespace-nowrap text-white transition hover:bg-brand-700 sm:px-4">{{ __('nav.register') }}</a>
            @endauth
        </div>
    </header>
</div>
