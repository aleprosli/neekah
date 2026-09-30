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
{{-- A sign-off, not a statement: the florals again, the tagline in the
     display face, and the contact details as links rather than a column of
     small grey lines. --}}
<x-slot:footer>
<x-mail::footer>
<img src="{{ asset('img/mail/divider.png') }}" class="divider" width="120" height="16" alt="">
<p class="footer-tagline">{{ app(App\Support\SeoSettings::class)->tagline() }}</p>
@if ($contact->email() || $contact->whatsappUrl())
<p class="footer-contact">
@if ($contact->email())<a href="mailto:{{ $contact->email() }}">{{ $contact->email() }}</a>@endif
@if ($contact->email() && $contact->whatsappUrl()) &nbsp;·&nbsp; @endif
@if ($contact->whatsappUrl())<a href="{{ $contact->whatsappUrl() }}">WhatsApp</a>@endif
</p>
@endif
<p class="footer-legal">© {{ date('Y') }} {{ config('app.name') }}</p>
</x-mail::footer>
</x-slot:footer>
</x-mail::layout>
