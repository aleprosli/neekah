@php $contact = app(App\Support\ContactSettings::class); @endphp
<x-mail::layout>
{{-- Header --}}
<x-slot:header>
<x-mail::header :url="config('app.url')">
{{ config('app.name') }}
</x-mail::header>
</x-slot:header>

{{-- Body --}}
{!! $slot !!}

{{-- Subcopy --}}
@isset($subcopy)
<x-slot:subcopy>
<x-mail::subcopy>
{!! $subcopy !!}
</x-mail::subcopy>
</x-slot:subcopy>
@endisset

{{-- Footer --}}
<x-slot:footer>
<x-mail::footer>
{{ config('app.name') }} — {{ app(App\Support\SeoSettings::class)->tagline() }}

@if ($contact->email() || $contact->phone())
{{ collect([$contact->email(), $contact->phone()])->filter()->implode(' · ') }}
@endif

© {{ date('Y') }} {{ config('app.name') }}. Hak cipta terpelihara.
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
