@props(['title', 'subtitle' => null, 'card' => true, 'audience' => null, 'switchUrl' => null])

{{-- Every sign-in, sign-up and password page. `card` off is the role chooser,
     which lays its own cards straight on the page; `audience` is the side the
     visitor picked, shown above the form with a way back to change it. --}}

@php app(App\Support\Seo::class)->noindex(); @endphp

<x-layouts.auth :title="$title">
    <div class="text-center">
        @if ($audience)
            <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-line bg-surface-raised px-3 py-1 text-xs font-medium text-ink-muted">
                <x-nav-icon :name="$audience->icon()" />
                {{ $audience->label() }}
                @if ($switchUrl)
                    <span class="text-line" aria-hidden="true">·</span>
                    <a href="{{ $switchUrl }}" class="font-semibold text-brand-600 hover:underline">Tukar</a>
                @endif
            </p>
        @endif

        <h1 class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-2 text-sm text-ink-muted">{{ $subtitle }}</p>
        @endif
    </div>

    {{-- A form shows its own notice; the chooser has none, so a "kata laluan
         telah ditukar" that lands here would otherwise vanish. --}}
    @if (! $card && session('status'))
        <p class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-900">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <ul class="mt-6 flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <div @class([
        'mt-8',
        'rounded-2xl border border-line bg-surface-raised p-6 shadow-xl shadow-brand-900/5 sm:p-8' => $card,
    ])>
        {{ $slot }}
    </div>
</x-layouts.auth>
