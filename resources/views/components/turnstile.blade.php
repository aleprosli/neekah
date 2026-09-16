@php $turnstile = app(App\Support\TurnstileSettings::class); @endphp

@if ($turnstile->isEnabled())
    {{-- The widget sizes itself to its container, so it never pushes a phone layout wider than the screen. --}}
    <div class="min-w-0 overflow-hidden">
        <div class="cf-turnstile" data-sitekey="{{ $turnstile->siteKey() }}" data-language="ms" data-size="flexible"></div>
    </div>

    @once
        @push('scripts')
            <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
        @endpush
    @endonce
@endif
