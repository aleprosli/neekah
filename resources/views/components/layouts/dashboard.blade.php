@props(['title', 'nav', 'area', 'heading' => null, 'subheading' => null])

@php
    app(App\Support\Seo::class)->noindex();
    $user = auth()->user();
    $home = match (true) {
        $user->isAdmin() => route('admin.dashboard'),
        $user->isVendor() => route('vendor.dashboard'),
        default => route('dashboard'),
    };
@endphp

{{-- The web-app shell every signed-in role works in: a sidebar that runs the
     full height of the window against the left edge, a thin top bar, and the
     page beside them. Nothing of the public site is here — no floating
     header, no footer — because this is where people do their work, not
     where they browse.

     On a phone the sidebar is a drawer. It opens through the checkbox below,
     so it needs no JavaScript, and it sits inside the swapped region so every
     navigation closes it again. --}}
<x-layouts.app :title="$title" shell="dashboard">
    <div class="min-h-screen bg-surface-muted/50">
        <div data-nav-region>
            <input type="checkbox" id="dashboard-drawer" class="peer sr-only" aria-hidden="true" tabindex="-1">

            <label for="dashboard-drawer" class="fixed inset-0 z-40 hidden bg-ink/40 backdrop-blur-[1px] peer-checked:block lg:hidden" aria-label="Tutup menu"></label>

            <aside class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col border-r border-line bg-surface-raised transition-transform duration-200 peer-checked:translate-x-0 lg:translate-x-0" aria-label="Dashboard">
                <div class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-line px-5">
                    <a href="{{ $home }}" class="flex min-w-0 items-center" aria-label="{{ config('app.name') }}">
                        <x-brand.lockup class="h-7 max-w-full object-contain" />
                    </a>
                    <label for="dashboard-drawer" class="-mr-2 flex size-9 cursor-pointer items-center justify-center rounded-lg text-ink-muted hover:bg-surface-muted lg:hidden" aria-label="Tutup menu">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    </label>
                </div>

                <p class="px-5 pt-5 pb-2 text-[11px] font-semibold tracking-wider text-ink-muted uppercase">{{ $area }}</p>

                <nav class="no-scrollbar flex-1 overflow-y-auto px-3 pb-4">
                    <ul class="flex flex-col gap-0.5">
                        @foreach ($nav as $item)
                            <li>
                                <a href="{{ $item['href'] }}" @class([
                                    'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                    'bg-brand-50 text-brand-700 [&_svg]:text-brand-600' => $item['active'],
                                    'text-ink-muted hover:bg-surface-muted hover:text-ink' => ! $item['active'],
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
                </nav>

                <div class="shrink-0 border-t border-line p-3">
                    <div class="flex items-center gap-3 rounded-lg px-2 py-2">
                        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-semibold text-white">{{ mb_substr($user->name, 0, 1) }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $user->name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $user->email }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink">
                            <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>
                            Log keluar
                        </button>
                    </form>
                </div>
            </aside>
        </div>

        <div class="flex min-h-screen flex-col lg:pl-64">
            <header class="sticky top-0 z-30 flex h-16 shrink-0 items-center gap-3 border-b border-line bg-surface-raised/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                <label for="dashboard-drawer" class="-ml-1 flex size-9 cursor-pointer items-center justify-center rounded-lg text-ink-muted hover:bg-surface-muted lg:hidden" aria-label="Buka menu">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
                </label>

                <a href="{{ $home }}" class="flex min-w-0 items-center lg:hidden" aria-label="{{ config('app.name') }}">
                    <x-brand.lockup class="h-6 max-w-[40vw] object-contain" />
                </a>

                <div class="ml-auto flex shrink-0 items-center gap-1">
                    <a href="{{ route('vendors.index') }}" class="hidden items-center gap-1.5 rounded-lg px-3 py-2 text-sm font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink sm:flex">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-9.5Z"/></svg>
                        Laman utama
                    </a>
                    <x-notification-bell />
                    <a href="{{ route('account.edit') }}" class="flex size-9 items-center justify-center rounded-full bg-brand-600 text-xs font-semibold text-white lg:hidden" aria-label="Akaun saya">{{ mb_substr($user->name, 0, 1) }}</a>
                </div>
            </header>

            <main class="mx-auto w-full max-w-[1440px] min-w-0 flex-1 px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
                @if ($heading)
                    <div class="mb-6 flex flex-col gap-3 break-words sm:flex-row sm:items-end sm:justify-between">
                        <div class="min-w-0">
                            <h1 class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">{{ $heading }}</h1>
                            @if ($subheading)
                                <p class="mt-1 text-sm text-ink-muted">{{ $subheading }}</p>
                            @endif
                        </div>
                        @isset($actions)
                            <div class="flex flex-wrap gap-2">{{ $actions }}</div>
                        @endisset
                    </div>
                @endif

                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">{{ session('status') }}</div>
                @endif

                @if ($errors->any())
                    <ul class="mb-6 flex flex-col gap-1 rounded-xl bg-brand-50 p-4 text-sm text-brand-800">
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
