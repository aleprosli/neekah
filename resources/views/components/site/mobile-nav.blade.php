<nav data-nav-region class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface/95 backdrop-blur md:hidden" aria-label="{{ __('nav.mobile') }}">
    <ul class="grid grid-cols-3 text-[11px] font-medium">
        <li>
            <a href="{{ route('vendors.index') }}" @class(['flex flex-col items-center gap-1 py-2', 'text-brand-600' => App\Support\Locales::routeIs('vendors.*'), 'text-ink-muted' => ! App\Support\Locales::routeIs('vendors.*')])>
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
                {{ __('nav.search') }}
            </a>
        </li>
        {{-- The phone has no header menu, so "Tentang" opens the same menu as
             the header dropdown in a sheet above the bar. --}}
        <li>
            <details data-popover class="group">
                <summary @class(['flex cursor-pointer list-none flex-col items-center gap-1 py-2 select-none [&::-webkit-details-marker]:hidden group-open:text-brand-600', 'text-brand-600' => App\Support\Locales::routeIs('landing', 'features.*', 'blog.*'), 'text-ink-muted' => ! App\Support\Locales::routeIs('landing', 'features.*', 'blog.*')])>
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 21-7.5-7.5a5 5 0 0 1 7.5-6.6 5 5 0 0 1 7.5 6.6Z"/></svg>
                    <span class="flex items-center gap-0.5">
                        {{ __('nav.about') }}
                        <svg class="size-3 rotate-180 transition group-open:rotate-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </span>
                </summary>
                <x-site.about-menu compact class="absolute inset-x-3 bottom-full mb-3 max-h-[calc(100dvh-7rem)] overflow-y-auto rounded-3xl border border-line bg-surface-raised p-3 text-sm shadow-2xl shadow-brand-900/20" />
            </details>
        </li>
        <li>
            @auth
                @php $home = match (true) { auth()->user()->isAdmin() => route('admin.dashboard'), auth()->user()->isVendor() => route('vendor.dashboard'), default => route('dashboard') }; @endphp
                <a href="{{ $home }}" @class(['flex flex-col items-center gap-1 py-2', 'text-brand-600' => App\Support\Locales::routeIs('dashboard', 'weddings.*', 'guests.*', 'bookings.*', 'enquiries.*', 'vendor.*', 'admin.*'), 'text-ink-muted' => ! App\Support\Locales::routeIs('dashboard', 'weddings.*', 'guests.*', 'bookings.*', 'enquiries.*', 'vendor.*', 'admin.*')])>
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    {{ __('nav.account') }}
                </a>
            @else
                <a href="{{ route('vendor.register') }}" class="flex flex-col items-center gap-1 py-2 text-ink-muted">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/></svg>
                    {{ __('nav.vendor') }}
                </a>
            @endauth
        </li>
    </ul>
</nav>
