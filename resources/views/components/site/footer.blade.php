@php
    $contact = app(App\Support\ContactSettings::class);
    $tagline = app(App\Support\SeoSettings::class)->tagline();
    $social = $contact->socialLinks();
    $status = config('neekah.status_url');
@endphp

<footer class="border-t border-line pb-16 md:pb-0">
    {{-- On a phone this is a lot of small text in one column, so the links sit
         in two tidy columns rather than wrapping raggedly, and the gaps are
         tighter than on a wide screen. --}}
    <div class="mx-auto flex max-w-[1760px] min-w-0 flex-col gap-5 px-4 py-7 text-sm text-ink-muted sm:gap-6 sm:px-6 sm:py-8 lg:px-10">
        <div class="flex flex-col gap-5 sm:flex-row sm:justify-between sm:gap-6">
            <nav class="grid grid-cols-2 gap-y-2.5 sm:flex sm:flex-wrap sm:gap-x-6 sm:gap-y-2" aria-label="{{ __('pages.footer.footer') }}">
                <a href="{{ route('landing') }}" class="transition hover:text-ink">{{ __('pages.footer.tentang') }}</a>
                <a href="{{ route('vendors.index') }}" class="transition hover:text-ink">{{ __('pages.footer.cari_vendor') }}</a>
                <a href="{{ route('vendor.register') }}" class="transition hover:text-ink">{{ __('pages.footer.jadi_vendor') }}</a>
                <a href="{{ route('blog.index') }}" class="transition hover:text-ink">{{ __('pages.footer.blog') }}</a>
            </nav>

            @if ($contact->phone() || $contact->email() || $contact->whatsappUrl())
                <div class="grid grid-cols-2 gap-y-2.5 sm:flex sm:flex-wrap sm:gap-x-6 sm:gap-y-2">
                    @if ($contact->telUrl())
                        <a href="{{ $contact->telUrl() }}" class="transition hover:text-ink">{{ $contact->phone() }}</a>
                    @endif
                    @if ($contact->whatsappUrl())
                        <a href="{{ $contact->whatsappUrl() }}" target="_blank" rel="noopener" class="transition hover:text-ink">{{ __('pages.footer.whatsapp') }}</a>
                    @endif
                    @if ($contact->email())
                        {{-- An address long enough to wrap would otherwise split
                             the two columns unevenly. --}}
                        <a href="mailto:{{ $contact->email() }}" class="col-span-2 truncate transition hover:text-ink sm:col-auto sm:overflow-visible sm:whitespace-normal">{{ $contact->email() }}</a>
                    @endif
                </div>
            @endif
        </div>

        @if ($contact->address() || $contact->hours() || $social)
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                <div class="flex flex-col gap-1">
                    @if ($contact->address())
                        <p class="max-w-md">{{ $contact->address() }}</p>
                    @endif
                    @if ($contact->hours())
                        <p>{{ $contact->hours() }}</p>
                    @endif
                </div>

                @if ($social)
                    <div class="flex flex-wrap gap-x-6 gap-y-2">
                        @foreach ($social as $link)
                            <a href="{{ $link['url'] }}" target="_blank" rel="noopener" class="transition hover:text-ink">{{ $link['label'] }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        @endif

        <div class="flex flex-col gap-2.5 border-t border-line pt-6 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
            <p>© {{ now()->year }} {{ config('app.name') }} · {{ $tagline }}</p>

            @if ($status)
                {{-- Hosted off our own infrastructure, so it still answers when
                     Neekah does not. No colour on the mark: we have not checked
                     the status here and must not appear to report one. --}}
                <a href="{{ $status }}" target="_blank" rel="noopener" class="inline-flex shrink-0 items-center gap-1.5 transition hover:text-ink">
                    <svg class="size-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
                    {{ __('pages.footer.status_sistem') }}
                </a>
            @endif
        </div>
    </div>
</footer>
