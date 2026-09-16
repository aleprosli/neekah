@props(['recommended' => null, 'note' => null])

@php $images = app(App\Support\ImageSettings::class); @endphp

{{-- One place says what an upload may be, so no page can promise a limit the server refuses. --}}
<span {{ $attributes->class('text-xs text-ink-muted') }}>
    {{ $images->acceptedFormatsLabel() }} · maksimum {{ $images->effectiveUploadMegabytes() }}MB
    @if ($recommended)
        · disyorkan {{ $recommended }}
    @endif
    @if ($note)
        <span class="block">{{ $note }}</span>
    @endif
    <span class="block">Gambar dikecilkan kepada {{ $images->maxDimension() }}px dan ditukar ke {{ strtoupper($images->format()) }} secara automatik.</span>
</span>
