@php
    /**
     * Google Analytics 4. The measurement ID is the only setting, and it is
     * public by design — it ships in the markup to every visitor. With no ID
     * configured nothing is rendered, so a local machine stays silent.
     *
     * Anything that is not a Google tag ID is dropped rather than printed:
     * this value lands inside a <script> block, where Blade's HTML escaping
     * would not be enough to make a stray value safe.
     */
    $measurementId = (string) config('services.google_analytics.measurement_id');
    $measurementId = preg_match('/^(G|GT)-[A-Z0-9]+$/i', $measurementId) ? $measurementId : '';
@endphp

@if ($measurementId !== '')
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $measurementId }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag() { dataLayer.push(arguments); }
        gtag('js', new Date());
        gtag('config', @json($measurementId));

        /**
         * resources/js/navigation.js swaps the page without a page load, so
         * gtag never sees the second visit and everything after it. The head
         * survives every swap, so this listener is registered exactly once.
         */
        window.addEventListener('neekah:navigated', function () {
            gtag('event', 'page_view', {
                page_location: window.location.href,
                page_title: document.title,
            });
        });
    </script>
@endif
