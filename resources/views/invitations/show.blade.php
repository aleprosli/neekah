@php $wedding = $invitation->wedding; @endphp

<x-layouts.app title="Jemputan majlis">
    <x-site.header />

    <main class="mx-auto flex min-h-[70vh] max-w-lg flex-col justify-center px-4 pt-24 pb-16 sm:px-6">
        <div class="rounded-3xl border border-line bg-surface-raised p-6 text-center shadow-xl shadow-brand-900/5 sm:p-8">
            <span class="text-4xl">💌</span>
            <h1 class="mt-3 font-display text-2xl font-semibold tracking-tight">{{ $invitation->inviter->name }} menjemput anda</h1>
            <p class="mt-2 text-sm text-ink-muted">Untuk menguruskan wedding project ini bersama-sama di Neekah.</p>

            <dl class="mt-6 flex flex-col gap-2 rounded-2xl bg-surface-muted p-5 text-left text-sm">
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">Majlis</dt><dd class="font-semibold">{{ $wedding->title }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">Tarikh</dt><dd class="font-semibold">{{ $wedding->event_date->translatedFormat('j F Y') }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">Lokasi</dt><dd class="font-semibold">{{ $wedding->city }}, {{ $wedding->state }}</dd></div>
                <div class="flex justify-between gap-3"><dt class="text-ink-muted">Bajet</dt><dd class="font-semibold">RM{{ number_format((float) $wedding->budget) }}</dd></div>
            </dl>

            @if ($errors->any())
                <ul class="mt-4 flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-sm text-brand-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($wedding->hasMember(auth()->user()))
                <p class="mt-6 text-sm text-ink-muted">Anda sudah menjadi ahli majlis ini.</p>
                <a href="{{ route('dashboard') }}" class="mt-3 inline-flex rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Buka majlis</a>
            @elseif (! $invitation->isPending())
                <p class="mt-6 rounded-xl bg-surface-muted p-4 text-sm text-ink-muted">Jemputan ini sudah tamat tempoh atau telah digunakan. Minta {{ $invitation->inviter->name }} menghantar jemputan baharu.</p>
            @else
                <p class="mt-6 text-sm text-ink-muted">Anda berdua akan berkongsi checklist, bajet, tempahan dan pembayaran yang sama.</p>
                <form method="POST" action="{{ route('invitations.accept', $invitation) }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Terima jemputan</button>
                </form>
                <a href="{{ route('vendors.index') }}" class="mt-3 inline-block text-sm text-ink-muted underline underline-offset-4">Nanti dahulu</a>
            @endif
        </div>

        <p class="mt-4 text-center text-xs text-ink-muted">Jemputan dihantar ke {{ $invitation->email }}. Anda log masuk sebagai {{ auth()->user()->email }}.</p>
    </main>

    <x-site.footer />
</x-layouts.app>
