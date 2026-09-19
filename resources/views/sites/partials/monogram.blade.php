@props(['site', 'size' => 'size-20 text-3xl'])
{{-- The couple's initials in a ringed seal, the crest printed at the head of a kad. --}}
<span class="nk-monogram {{ $size }}" aria-hidden="true">{{ $site->initials() }}</span>
