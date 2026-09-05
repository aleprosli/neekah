@props(['site', 'preview' => false, 'tone' => 'light'])

@php
    $dark = $tone === 'dark';
    $field = $dark
        ? 'w-full rounded-xl border border-white/25 bg-white/10 px-4 py-2.5 text-sm text-white placeholder:text-white/50 focus:border-white/60 focus:outline-none'
        : 'w-full rounded-xl border border-black/10 bg-white px-4 py-2.5 text-sm text-neutral-900 focus:border-black/40 focus:outline-none';
    $button = $dark
        ? 'w-full rounded-full bg-white py-3 text-sm font-semibold text-neutral-900 transition hover:opacity-90'
        : 'w-full rounded-full bg-neutral-900 py-3 text-sm font-semibold text-white transition hover:opacity-90';
@endphp

<div class="mx-auto w-full max-w-sm">
    @if (session('rsvp'))
        <p class="rounded-2xl border border-emerald-300 bg-emerald-50 px-4 py-3 text-center text-sm text-emerald-900">{{ session('rsvp') }}</p>
    @else
        @if ($errors->any())
            <ul class="mb-3 rounded-2xl bg-red-50 px-4 py-3 text-left text-xs text-red-800">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <form method="POST" action="{{ $preview ? '#' : route('sites.rsvp', ['subdomain' => $site->subdomain]) }}" class="flex flex-col gap-3" @if ($preview) onsubmit="return false" @endif>
            @csrf
            <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama anda" required class="{{ $field }}">
            <input type="tel" name="phone" value="{{ old('phone') }}" placeholder="Nombor telefon" class="{{ $field }}">

            <div class="grid grid-cols-2 gap-3">
                <label class="cursor-pointer">
                    <input type="radio" name="attending" value="1" class="peer sr-only" checked>
                    <span @class(['block rounded-xl border py-2.5 text-center text-sm transition', 'border-white/25 peer-checked:border-white peer-checked:bg-white peer-checked:text-neutral-900' => $dark, 'border-black/10 bg-white peer-checked:border-neutral-900 peer-checked:bg-neutral-900 peer-checked:text-white' => ! $dark])>Hadir</span>
                </label>
                <label class="cursor-pointer">
                    <input type="radio" name="attending" value="0" class="peer sr-only">
                    <span @class(['block rounded-xl border py-2.5 text-center text-sm transition', 'border-white/25 peer-checked:border-white peer-checked:bg-white peer-checked:text-neutral-900' => $dark, 'border-black/10 bg-white peer-checked:border-neutral-900 peer-checked:bg-neutral-900 peer-checked:text-white' => ! $dark])>Tidak hadir</span>
                </label>
            </div>

            <input type="number" name="pax" value="{{ old('pax', 1) }}" min="1" max="20" placeholder="Bilangan orang" class="{{ $field }}">
            <textarea name="message" rows="2" placeholder="Ucapan untuk pengantin" class="{{ $field }}">{{ old('message') }}</textarea>
            <button type="submit" class="{{ $button }}">Hantar RSVP</button>
        </form>

        @if ($site->rsvp_deadline)
            <p @class(['mt-2 text-center text-xs', 'text-white/60' => $dark, 'text-neutral-500' => ! $dark])>Sila jawab sebelum {{ $site->rsvp_deadline->translatedFormat('j F Y') }}.</p>
        @endif
    @endif
</div>
