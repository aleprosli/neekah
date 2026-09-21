@php
    $contact = app(App\Support\ContactSettings::class);
    $tagline = app(App\Support\SeoSettings::class)->tagline();
    $social = $contact->socialLinks();
@endphp

<footer class="border-t border-line pb-16 md:pb-0">
    <div class="mx-auto flex max-w-[1760px] min-w-0 flex-col gap-6 px-4 py-8 text-sm text-ink-muted sm:px-6 lg:px-10">
        <div class="flex flex-col gap-6 sm:flex-row sm:justify-between">
            <nav class="flex flex-wrap gap-x-6 gap-y-2" :aria-label="__('pages.footer.footer')">
                <a href="{{ route('landing') }}" class="transition hover:text-ink">{{ __('pages.footer.tentang') }}</a>
                <a href="{{ route('vendors.index') }}" class="transition hover:text-ink">{{ __('pages.footer.cari_vendor') }}</a>
                <a href="{{ route('vendor.register') }}" class="transition hover:text-ink">{{ __('pages.footer.jadi_vendor') }}</a>
                <a href="{{ route('blog.index') }}" class="transition hover:text-ink">{{ __('pages.footer.blog') }}</a>
            </nav>

            @if ($contact->phone() || $contact->email() || $contact->whatsappUrl())
                <div class="flex flex-wrap gap-x-6 gap-y-2">
                    @if ($contact->telUrl())
                        <a href="{{ $contact->telUrl() }}" class="transition hover:text-ink">{{ $contact->phone() }}</a>
                    @endif
                    @if ($contact->whatsappUrl())
                        <a href="{{ $contact->whatsappUrl() }}" target="_blank" rel="noopener" class="transition hover:text-ink">{{ __('pages.footer.whatsapp') }}</a>
                    @endif
                    @if ($contact->email())
                        <a href="mailto:{{ $contact->email() }}" class="break-all transition hover:text-ink">{{ $contact->email() }}</a>
                    @endif
                </div>
            @endif
        </div>

        @if ($contact->address() || $contact->hours() || $social)
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
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

        <p class="border-t border-line pt-6">© {{ now()->year }} {{ config('app.name') }} · {{ $tagline }}</p>
    </div>
</footer>
