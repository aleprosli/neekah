@props(['title', 'nav', 'context' => [], 'heading' => null, 'subheading' => null])

@php
    app(App\Support\Seo::class)->noindex();
    $user = auth()->user();
    $home = match (true) {
        $user->isAdmin() => route('admin.dashboard'),
        $user->isVendor() => route('vendor.dashboard'),
        default => route('dashboard'),
    };
    $onAccount = App\Support\Locales::routeIs('account.*');
@endphp

{{-- The web-app shell every signed-in role works in: a sidebar that runs the
     full height of the window against the left edge, a thin top bar, and the
     page beside them.

     It is dressed like the rest of Neekah rather than like a generic admin
     panel: ivory paper instead of grey, blush and a touch of gold, the display
     serif for headings, and the two overlapping rings of the brand mark.

     `nav` is a list of groups, [{label, items: [{label, icon, href, active,
     badge}]}], so a long menu reads as a few short ones. The account itself
     is not in it: it is the card at the foot of the sidebar.

     On a phone the sidebar is a drawer. It opens through the checkbox below,
     so it needs no JavaScript, and it sits inside the swapped region so every
     navigation closes it again. --}}
<x-layouts.app :title="$title" shell="dashboard">
    <div class="min-h-screen bg-ivory">
        <div data-nav-region>
            <input type="checkbox" id="dashboard-drawer" class="peer sr-only" aria-hidden="true" tabindex="-1">

            <label for="dashboard-drawer" class="fixed inset-0 z-40 hidden bg-brand-900/30 backdrop-blur-[2px] peer-checked:block lg:hidden" aria-label="Tutup menu"></label>

            <aside class="fixed inset-y-0 left-0 z-40 flex w-[17rem] -translate-x-full flex-col overflow-hidden border-r border-gold-300/50 bg-surface-raised bg-linear-to-b from-surface-raised via-surface-raised to-brand-50 transition-transform duration-200 peer-checked:translate-x-0 lg:translate-x-0" aria-label="Dashboard">
                {{-- The brand's two rings, faint, behind the account card. --}}
                <svg class="pointer-events-none absolute -bottom-24 -left-16 size-80 text-gold-400/25" viewBox="0 0 200 200" fill="none" stroke="currentColor" stroke-width="1" aria-hidden="true">
                    <circle cx="80" cy="100" r="60" />
                    <circle cx="120" cy="100" r="60" />
                    <circle cx="80" cy="100" r="44" stroke-dasharray="1 5" />
                    <circle cx="120" cy="100" r="44" stroke-dasharray="1 5" />
                </svg>

                <div class="relative flex h-16 shrink-0 items-center justify-between gap-2 px-6">
                    <a href="{{ $home }}" class="flex min-w-0 items-center" aria-label="{{ config('app.name') }}">
                        <x-brand.lockup class="h-8 max-w-full object-contain" />
                    </a>
                    <label for="dashboard-drawer" class="-mr-2 flex size-9 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-brand-50 lg:hidden" aria-label="Tutup menu">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </label>
                </div>

                <div class="relative mx-6 flex items-center gap-2 text-gold-500" aria-hidden="true">
                    <span class="h-px flex-1 bg-linear-to-r from-transparent to-gold-300"></span>
                    <svg class="size-2.5" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 6.2 3.8 10 5 6.2 6.2 5 10 3.8 6.2 0 5 3.8 3.8Z"/></svg>
                    <span class="h-px flex-1 bg-linear-to-l from-transparent to-gold-300"></span>
                </div>

                <nav class="no-scrollbar relative flex-1 overflow-y-auto px-4 pt-4 pb-6">
                    @foreach ($nav as $group)
                        <div @class(['mt-5' => ! $loop->first])>
                            @if ($group['label'])
                                <p class="mb-1.5 px-3 font-display text-[13px] text-gold-600 italic">{{ $group['label'] }}</p>
                            @endif
                            <ul class="flex flex-col gap-0.5">
                                @foreach ($group['items'] as $item)
                                    <li>
                                        <a href="{{ $item['href'] }}" @class([
                                            'group flex items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium transition',
                                            'bg-surface-raised text-brand-700 shadow-sm shadow-brand-900/5 ring-1 ring-brand-100 [&_svg]:text-brand-600' => $item['active'],
                                            'text-ink-muted hover:bg-surface-raised/70 hover:text-ink' => ! $item['active'],
                                        ]) @if ($item['active']) aria-current="page" @endif>
                                            <x-nav-icon :name="$item['icon']" />
                                            <span class="min-w-0 flex-1 truncate">{{ $item['label'] }}</span>
                                            @if (! empty($item['badge']))
                                                <span class="rounded-full bg-brand-600 px-1.5 py-px text-[11px] font-semibold text-white">{{ $item['badge'] }}</span>
                                            @endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </nav>

                <div class="relative shrink-0 p-4">
                    <div @class([
                        'rounded-2xl border bg-surface-raised/90 p-2 shadow-sm backdrop-blur',
                        'border-brand-200 ring-1 ring-brand-100' => $onAccount,
                        'border-gold-300/60' => ! $onAccount,
                    ])>
                        <a href="{{ route('account.edit') }}" class="flex items-center gap-3 rounded-xl px-2 py-2 transition hover:bg-brand-50/60" @if ($onAccount) aria-current="page" @endif>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-full bg-linear-to-br from-brand-500 to-brand-700 font-display text-base font-semibold text-white ring-2 ring-gold-300 ring-offset-2 ring-offset-surface-raised">{{ mb_substr($user->name, 0, 1) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate text-sm font-semibold">{{ $user->name }}</span>
                                <span class="block truncate text-xs text-ink-muted">Akaun saya</span>
                            </span>
                        </a>

                        <form method="POST" action="{{ route('logout') }}" class="mt-1 border-t border-line/70 pt-1">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-sm font-medium text-ink-muted transition hover:bg-brand-50/60 hover:text-brand-700">
                                <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                                Log keluar
                            </button>
                        </form>
                    </div>
                </div>
            </aside>
        </div>

        <div class="flex min-h-screen flex-col lg:pl-[17rem]">
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-gold-300/40 bg-ivory/85 px-4 backdrop-blur-md sm:px-6 lg:px-10">
                <label for="dashboard-drawer" class="-ml-1 flex size-9 cursor-pointer items-center justify-center rounded-full text-ink-muted hover:bg-brand-50 lg:hidden" aria-label="Buka menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </label>

                {{-- Where you are: the wedding and its countdown, the business
                     and its status, or simply the admin panel and today. --}}
                @if (! empty($context['title']))
                    <div class="flex min-w-0 items-center gap-3">
                        @if (! empty($context['wedding']))
                            <span class="hidden size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 text-brand-600 ring-1 ring-gold-300/70 sm:flex" aria-hidden="true">
                                <x-nav-icon name="rings" />
                            </span>
                        @endif
                        <div class="min-w-0 leading-tight">
                            <p class="truncate font-display text-base font-semibold sm:text-lg">{{ $context['title'] }}</p>
                            @if (! empty($context['detail']))
                                <p class="truncate text-xs text-ink-muted">{{ $context['detail'] }}</p>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="ml-auto flex shrink-0 items-center gap-1">
                    <a href="{{ route('vendors.index') }}" class="hidden items-center gap-1.5 rounded-full px-3 py-2 text-sm font-medium text-ink-muted transition hover:bg-brand-50 hover:text-brand-700 md:flex">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z"/></svg>
                        Laman utama
                    </a>
                    <x-site.language-switcher />
                    <x-notification-bell />
                </div>
            </header>

            <main class="relative mx-auto w-full max-w-[1400px] min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-10 lg:py-10">
                @if ($heading)
                    <div class="mb-8 flex flex-col gap-4 break-words sm:flex-row sm:items-end sm:justify-between">
                        <div class="min-w-0">
                            <h1 class="font-display text-3xl font-semibold tracking-tight">{{ $heading }}</h1>
                            @if ($subheading)
                                <p class="mt-1.5 text-sm text-ink-muted">{{ $subheading }}</p>
                            @endif
                            <span class="mt-4 flex items-center gap-1.5 text-gold-500" aria-hidden="true">
                                <span class="h-px w-8 bg-gold-400"></span>
                                <svg class="size-2" viewBox="0 0 10 10" fill="currentColor"><path d="M5 0 6.2 3.8 10 5 6.2 6.2 5 10 3.8 6.2 0 5 3.8 3.8Z"/></svg>
                                <span class="h-px w-3 bg-gold-300"></span>
                            </span>
                        </div>
                        @isset($actions)
                            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
                        @endisset
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6 flex items-start gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-5 py-4 text-sm text-emerald-900">
                        <svg class="mt-0.5 size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <ul class="mb-6 flex flex-col gap-1 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</x-layouts.app>
