@props(['site'])

@if ($site->showsGift())
    <section id="hadiah" data-reveal class="scroll-mt-8 py-12 text-center">
        @include('sites.partials.section-heading', ['eyebrow' => 'Salam kaut', 'title' => 'Hadiah'])
        @if ($site->gift_note)
            <p class="nk-body mt-5 text-sm">{{ $site->gift_note }}</p>
        @endif
        <div class="nk-plaque mt-7 flex flex-col items-center gap-6 px-5 py-7">
            @if ($site->giftQrUrl())
                <img data-card-gift-qr src="{{ $site->giftQrUrl() }}" alt="Kod QR DuitNow" class="w-48 rounded-lg bg-white p-3">
            @endif
            @foreach ($site->gift_accounts ?? [] as $account)
                <div class="w-full">
                    <p class="nk-muted text-xs tracking-[0.2em] uppercase">{{ $account['bank'] }}</p>
                    <p class="nk-name mt-1 text-xl tabular-nums">{{ $account['number'] }}</p>
                    <p class="nk-body text-sm">{{ $account['holder'] }}</p>
                    <button type="button" data-copy="{{ $account['number'] }}" class="nk-button mt-3 rounded-full px-6 py-2 text-xs font-semibold">Salin nombor akaun</button>
                </div>
            @endforeach
        </div>
    </section>
@endif
