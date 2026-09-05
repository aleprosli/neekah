@props(['site', 'template', 'preview' => false, 'sample' => false])

<x-layouts.app :title="$site->coupleNames()" :description="'Jemputan majlis perkahwinan '.$site->coupleNames()" :preloader="false">
    @if ($preview)
        <div class="sticky top-0 z-50 bg-ink text-surface">
            <div class="mx-auto flex max-w-4xl flex-col items-center justify-between gap-2 px-4 py-2 text-sm sm:flex-row sm:px-6">
                <p class="flex items-center gap-2">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    {{ $sample ? 'Contoh template '.$template->name : 'Pratonton kad anda' }}
                </p>
                <div class="flex gap-2">
                    @if ($sample)
                        <a href="{{ route('sites.templates') }}" class="rounded-full border border-surface/30 px-4 py-1.5 text-xs font-medium transition hover:bg-surface/10">Semua template</a>
                        <a href="{{ auth()->check() ? route('site.edit', ['template' => $template->slug]) : route('register') }}" class="rounded-full bg-surface px-4 py-1.5 text-xs font-semibold text-ink transition hover:opacity-90">Guna template ini</a>
                    @else
                        <a href="{{ route('site.edit') }}" class="rounded-full bg-surface px-4 py-1.5 text-xs font-semibold text-ink transition hover:opacity-90">Kembali ke editor</a>
                    @endif
                </div>
            </div>
        </div>
    @endif

    {{ $slot }}

    @unless ($preview)
        <p class="bg-surface py-6 text-center text-xs text-ink-muted">
            Kad jemputan digital oleh <a href="{{ route('landing') }}" class="font-medium underline underline-offset-4">Neekah</a>
        </p>
    @endunless
</x-layouts.app>
