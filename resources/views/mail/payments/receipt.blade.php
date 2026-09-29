{{-- The receipt email: the whole receipt, so it stands on its own in an
     inbox, and a link to the page that prints it. Markdown: nothing here may
     be indented, or it turns into a code block. --}}
@php
    $cell = fn (?string $value): string => str_replace(['|', "\n"], ['\|', ' '], e((string) $value));
    $issuer = $document->issuer();
    $billTo = $document->billTo();
@endphp
<x-mail::message>
# {{ $greeting }}

{{ __('notifications.payment_receipt.intro') }}
@if ($next)

{{ $next }}
@endif

<x-mail::panel>
**{{ mb_strtoupper($document->title()) }} · {{ $document->number() }}**<br>
{{ __('pages.receipt.amount_paid') }}: **{{ $document->total() }}**
</x-mail::panel>

<x-mail::table>
| {{ __('pages.receipt.from') }} | {{ __('pages.receipt.bill_to') }} |
|:--|:--|
| **{!! $cell($issuer['name']) !!}**@foreach ($issuer['lines'] as $line)<br>{!! $cell($line) !!}@endforeach | **{!! $cell($billTo['name']) !!}**@foreach ($billTo['lines'] as $line)<br>{!! $cell($line) !!}@endforeach |
</x-mail::table>

<x-mail::table>
| {{ __('pages.receipt.item') }} | {{ __('pages.receipt.amount') }} |
|:--|--:|
@foreach ($document->items() as $item)
| **{!! $cell($item['description']) !!}**@if ($item['detail'])<br>{!! $cell($item['detail']) !!}@endif | {{ $item['amount'] }} |
@endforeach
| **{{ __('pages.receipt.total') }}** | **{{ $document->total() }}** |
</x-mail::table>

<x-mail::table>
| | |
|:--|--:|
@foreach ($document->facts() as $label => $value)
| {!! $cell($label) !!} | {!! $cell($value) !!} |
@endforeach
@foreach ($document->bookingSummary() as $label => $value)
| {!! $cell($label) !!} | {!! $cell($value) !!} |
@endforeach
</x-mail::table>

<x-mail::button :url="$url">
{{ __('notifications.payment_receipt.action') }}
</x-mail::button>

{{ $document->footnote() }}

{!! nl2br(e($salutation)) !!}
</x-mail::message>
