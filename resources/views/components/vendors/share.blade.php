@props(['vendor'])

@php
    $url = route('vendors.show', $vendor);
    $text = $vendor->name.' di Neekah';

    // Instagram and TikTok have no share link a web page can open, so they are
    // reached through the phone's own share sheet ("Kongsi…") or the copied link.
    $targets = [
        'WhatsApp' => 'https://api.whatsapp.com/send?text='.rawurlencode($text.' '.$url),
        'Facebook' => 'https://www.facebook.com/sharer/sharer.php?u='.rawurlencode($url),
        'Threads' => 'https://www.threads.com/intent/post?text='.rawurlencode($text.' '.$url),
        'X' => 'https://x.com/intent/post?text='.rawurlencode($text).'&url='.rawurlencode($url),
        'Telegram' => 'https://t.me/share/url?url='.rawurlencode($url).'&text='.rawurlencode($text),
    ];
@endphp

<div {{ $attributes->class(['flex flex-col gap-2']) }}>
    <p class="text-sm font-medium">{{ __('pages.share.kongsi_vendor_ini') }}</p>
    <ul class="flex flex-wrap gap-2 text-xs font-semibold">
        <li hidden data-share-item>
            <button type="button" data-share-url="{{ $url }}" data-share-title="{{ $text }}" class="rounded-full bg-brand-600 px-3.5 py-2 text-white transition hover:bg-brand-700">{{ __('pages.share.kongsi') }}</button>
        </li>
        @foreach ($targets as $label => $href)
            <li><a href="{{ $href }}" target="_blank" rel="noopener nofollow" class="block rounded-full border border-line px-3.5 py-2 transition hover:border-brand-400">{{ $label }}</a></li>
        @endforeach
        <li><button type="button" data-copy="{{ $url }}" class="rounded-full border border-line px-3.5 py-2 transition hover:border-brand-400">{{ __('pages.share.salin_pautan') }}</button></li>
    </ul>
</div>
