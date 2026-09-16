@php $turnstile = app(App\Support\TurnstileSettings::class); @endphp

@if ($turnstile->isEnabled())
    {{-- resources/js/components/ui/UiTurnstile.vue --}}
    <div data-vue="ui-turnstile" data-props="@vueProps(['siteKey' => $turnstile->siteKey()])"></div>
@endif
