@props(['site', 'preview' => false, 'guest' => null])

<div class="mx-auto w-full max-w-sm">
    @if (session('rsvp'))
        <p class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-900">{{ session('rsvp') }}</p>
    @else
        @if ($errors->any())
            <ul class="mb-3 rounded-2xl bg-red-50 px-4 py-3 text-left text-xs text-red-800">
                @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
            </ul>
        @endif

        <form method="POST" action="{{ $preview ? '#' : route('sites.rsvp', ['subdomain' => $site->subdomain]) }}" class="flex flex-col gap-3" @if ($preview) onsubmit="return false" @endif>
            @csrf
            @if ($guest)
                <input type="hidden" name="u" value="{{ $guest['token'] }}">
                @if ($guest['has_responded'])
                    <p class="nk-muted text-center text-xs">Anda sudah menjawab. Hantar sekali lagi untuk mengemas kini jawapan anda.</p>
                @endif
            @endif
            <input type="text" name="name" value="{{ old('name', $guest['name'] ?? '') }}" placeholder="Nama anda" required class="nk-field w-full rounded-xl px-4 py-2.5 text-sm">
            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="Nombor telefon" class="nk-field w-full rounded-xl px-4 py-2.5 text-sm">
            <div class="grid grid-cols-2 gap-3">
                @foreach ([1 => 'Hadir', 0 => 'Tidak hadir'] as $value => $label)
                    <label class="cursor-pointer">
                        <input type="radio" name="attending" value="{{ $value }}" class="peer sr-only" @checked($value === 1)>
                        <span class="nk-field block rounded-xl py-2.5 text-center text-sm transition peer-checked:[background:var(--nk-button-bg)] peer-checked:[color:var(--nk-button-text)]">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            <input type="number" name="pax" value="{{ old('pax', $guest['pax_invited'] ?? 1) }}" min="1" max="{{ $guest['pax_invited'] ?? 20 }}" placeholder="Bilangan orang" class="nk-field w-full rounded-xl px-4 py-2.5 text-sm">
            <textarea name="message" rows="2" placeholder="Ucapan untuk pengantin" class="nk-field w-full rounded-xl px-4 py-2.5 text-sm">{{ old('message') }}</textarea>
            <button type="submit" class="nk-button w-full rounded-full py-3 text-sm font-semibold">Hantar RSVP</button>
        </form>

        @if ($site->rsvp_deadline)
            <p class="nk-muted mt-2 text-center text-xs">Sila jawab sebelum {{ $site->rsvp_deadline->translatedFormat('j F Y') }}.</p>
        @endif
    @endif
</div>
