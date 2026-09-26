{{-- Pro Elite: a Pro vendor whose earned tier is Top or Recommended. Gold on
     ink, apart from the plain Pro mark and the tier pills, and its title says
     how it is earned, so nobody reads it as something bought. --}}
<span {{ $attributes->class(['inline-flex shrink-0 items-center gap-1 rounded-md bg-linear-to-r from-ink to-brand-900 px-1.5 py-0.5 text-[10px] leading-none font-bold tracking-wider text-gold-300 uppercase']) }} title="{{ __('marketplace.card.elite_title') }}">✦ {{ __('marketplace.card.elite') }}</span>
