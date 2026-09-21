{{--
    The pager for every list in the application, set as the default view in
    AppServiceProvider. It prints a window (1 … 5 6 7 … 14) rather than every
    page, because a list of 160 vendors is 14 numbers and ran off the screen.
    Below sm even the window is dropped for "Page 5 of 14", which cannot
    overflow however many pages there are.
--}}
@if ($paginator->hasPages())
    @php
        $box = 'flex h-9 min-w-9 items-center justify-center rounded-full px-3 text-sm';
    @endphp

    <nav class="mt-12 flex items-center justify-center gap-1" aria-label="{{ __('pages.errors.halaman') }}">
        @if ($paginator->onFirstPage())
            <span class="{{ $box }} text-ink-muted/40" aria-disabled="true" aria-label="{{ __('pages.errors.sebelum') }}">&lsaquo;</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="{{ $box }} transition hover:bg-surface-muted" aria-label="{{ __('pages.errors.sebelum') }}">&lsaquo;</a>
        @endif

        {{-- A phone gets the count; there is no room for numbers. --}}
        <span class="{{ $box }} text-ink-muted sm:hidden">{{ __('pages.errors.halaman_x_dari_y', ['current' => $paginator->currentPage(), 'last' => $paginator->lastPage()]) }}</span>

        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="{{ $box }} hidden text-ink-muted sm:flex" aria-hidden="true">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="{{ $box }} hidden bg-brand-600 font-medium text-white sm:flex" aria-current="page">{{ $page }}</span>
                    @else
                        <a href="{{ $url }}" class="{{ $box }} hidden font-medium transition hover:bg-surface-muted sm:flex" aria-label="{{ __('pages.errors.halaman_nombor', ['page' => $page]) }}">{{ $page }}</a>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="{{ $box }} transition hover:bg-surface-muted" aria-label="{{ __('pages.errors.seterusnya') }}">&rsaquo;</a>
        @else
            <span class="{{ $box }} text-ink-muted/40" aria-disabled="true" aria-label="{{ __('pages.errors.seterusnya') }}">&rsaquo;</span>
        @endif
    </nav>
@endif
