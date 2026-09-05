@php $seconds = max(0, (float) config('neekah.preloader.seconds')); @endphp

{{-- Shown from the first paint and taken down at whichever comes later, the
     hold below or the page finishing loading. It is deliberately quiet: the
     brand, a thin indeterminate bar, and nothing that pretends to know how far
     along the page actually is.

     The failsafe outlasts the hold, so a stuck script still uncovers the page. --}}
<div id="nk-preloader" role="status" aria-live="polite"
     data-min-seconds="{{ $seconds }}"
     style="--nk-preloader-failsafe: {{ $seconds + 5 }}s">
    <div class="nk-preloader-inner">
        <x-brand.lockup class="h-11 max-w-[70vw] object-contain sm:h-14" />
        <div class="nk-preloader-bar" aria-hidden="true"><span></span></div>
        <span class="sr-only">Memuatkan {{ config('app.name') }}</span>
    </div>
</div>

{{-- Without JavaScript there is nothing to remove the overlay, so hide it. --}}
<noscript><style>#nk-preloader{display:none}</style></noscript>
