@props(['wedding', 'compact' => false])

@php
    $setup = new \App\Support\InvitationSetup($wedding);
    $steps = $setup->steps();
    $next = $setup->next();
    $done = $setup->completed();
@endphp

{{-- The guide from no card to guests holding it. Couples were signing up and
     never making one, so this walks them through it until the last step is done. --}}
@unless ($setup->isFinished())
    <section {{ $attributes->class(['overflow-hidden rounded-3xl border border-gold-400/60 bg-linear-to-br from-gold-300/15 via-surface-raised to-brand-50/60']) }} aria-label="{{ __('pages.card_setup.aria') }}">
        <div @class(['flex flex-col gap-6 p-6 sm:p-7', 'lg:flex-row lg:items-start lg:gap-10' => ! $compact])>
            <div @class(['min-w-0', 'lg:w-72 lg:shrink-0' => ! $compact])>
                <p class="flex items-center gap-2 text-xs font-semibold tracking-wide text-gold-600 uppercase">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg>
                    {{ __('pages.card_setup.eyebrow') }}
                </p>
                <h2 class="mt-2 font-display text-xl font-semibold">
                    {{ $done === 0 ? __('pages.card_setup.heading_start') : __('pages.card_setup.heading_continue') }}
                </h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('pages.card_setup.progress', ['done' => $done, 'total' => count($steps)]) }}</p>
                <x-progress-bar class="mt-4" :value="$done" :max="count($steps)" />
                @if ($next && ! $compact)
                    <a href="{{ $next['url'] }}" class="mt-5 inline-flex items-center gap-2 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        {{ $next['action'] }}
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                    </a>
                @endif
            </div>

            <ol @class(['grid min-w-0 flex-1 gap-3', 'sm:grid-cols-2' => ! $compact, 'sm:grid-cols-4' => $compact])>
                @foreach ($steps as $step)
                    @php $current = $next && $next['key'] === $step['key']; @endphp
                    <li @class([
                        'flex gap-3 rounded-2xl border p-4',
                        'border-brand-300 bg-surface-raised shadow-sm' => $current,
                        'border-transparent bg-surface-raised/60' => ! $current,
                    ])>
                        <span @class([
                            'flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                            'bg-emerald-500 text-white' => $step['done'],
                            'bg-brand-600 text-white' => $current,
                            'border border-line text-ink-muted' => ! $step['done'] && ! $current,
                        ])>
                            @if ($step['done'])
                                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                                <span class="sr-only">{{ __('pages.card_setup.done_label') }}</span>
                            @else
                                {{ $loop->iteration }}
                            @endif
                        </span>
                        <span class="min-w-0">
                            <span @class(['block text-sm font-semibold', 'text-ink-muted line-through decoration-ink-muted/40' => $step['done']])>{{ $step['title'] }}</span>
                            @unless ($step['done'] || $compact)
                                <span class="mt-0.5 block text-xs break-words text-ink-muted">{{ $step['hint'] }}</span>
                            @endunless
                            @if ($current && $compact)
                                <span class="mt-0.5 block text-xs break-words text-ink-muted">{{ $step['hint'] }}</span>
                            @endif
                        </span>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>
@endunless
