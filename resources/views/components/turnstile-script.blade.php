@if (app(App\Support\TurnstileSettings::class)->isEnabled())
    @once
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endonce
@endif
