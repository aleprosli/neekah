@props(['invitation'])

@php $url = route('invitations.show', $invitation); @endphp

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    <p class="text-xs font-medium text-ink-muted">Kongsi pautan ini melalui WhatsApp atau apa sahaja:</p>
    <div class="flex items-center gap-2 rounded-xl border border-line bg-surface p-1.5">
        <input type="text" value="{{ $url }}" readonly aria-label="Pautan jemputan" class="min-w-0 flex-1 bg-transparent px-2 text-xs text-ink-muted focus:outline-none" onfocus="this.select()">
        <button type="button" data-copy="{{ $url }}" class="shrink-0 rounded-lg bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700">Salin</button>
    </div>
    <a href="https://wa.me/?text={{ urlencode('Jom uruskan majlis kita di Neekah: '.$url) }}" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 rounded-full border border-line px-4 py-2 text-xs font-semibold transition hover:border-brand-400">
        <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.5 14.4c-.3-.2-1.7-.9-2-1s-.5-.1-.7.1-.7 1-.9 1.2-.3.2-.6.1a8 8 0 0 1-2.4-1.5 9 9 0 0 1-1.6-2c-.2-.3 0-.5.1-.6l.5-.6.3-.5v-.5l-1-2.3c-.2-.6-.5-.5-.7-.5h-.6a1.2 1.2 0 0 0-.8.4A3.4 3.4 0 0 0 6 9c0 1.5 1.1 3 1.3 3.2A12 12 0 0 0 12 16.5c2.2.8 2.2.5 2.6.5a3 3 0 0 0 2-1.4 2.5 2.5 0 0 0 .2-1.4l-.3-.2ZM12 2a10 10 0 0 0-8.6 15L2 22l5.2-1.4A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3.1.8.8-3-.2-.3A8.2 8.2 0 1 1 12 20.2Z"/></svg>
        Hantar melalui WhatsApp
    </a>
</div>
