@props(['site', 'class' => ''])
{{-- Day | date | month and year, the way the date is set on a printed kad. --}}
<div class="flex items-center justify-center gap-4 {{ $class }}">
    <span class="nk-body w-24 text-right text-xs tracking-[0.3em] uppercase">{{ $site->event_date->translatedFormat('l') }}</span>
    <span class="nk-hairline flex flex-col items-center border-x px-4">
        <span class="nk-name text-4xl leading-none font-medium tabular-nums">{{ $site->event_date->format('j') }}</span>
    </span>
    <span class="nk-body w-24 text-left text-xs leading-relaxed tracking-[0.3em] uppercase">{{ $site->event_date->translatedFormat('F') }}<br>{{ $site->event_date->format('Y') }}</span>
</div>
@if ($site->startsAtLabel())
    <p class="nk-muted mt-4 text-center text-sm tracking-[0.18em]">{{ $site->startsAtLabel() }}@if ($site->endsAtLabel()) &ndash; {{ $site->endsAtLabel() }}@endif</p>
@endif
