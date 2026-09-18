<x-layouts.customer title="Majlis saya" :heading="$wedding?->title ?? 'Majlis saya'" :subheading="$wedding ? $wedding->event_date->translatedFormat('l, j F Y').' · '.$wedding->city.', '.$wedding->state : 'Cipta wedding project untuk mula merancang.'">
    <x-slot:actions>
        @if ($wedding)
            <a href="{{ route('weddings.edit', $wedding) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Edit majlis</a>
        @endif
        @if (auth()->user()->canBecomeVendor())
            <a href="{{ route('vendor.convert') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Saya vendor</a>
        @endif
        <a href="{{ route('vendors.index') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Cari vendor</a>

        {{-- The one button on this page that stands out: the digital card is
             what a couple shares with every guest, and the feature they are
             least likely to find on their own. Once it is published it stops
             asking them to make one. --}}
        @if ($wedding)
            <a href="{{ route('site.edit') }}" class="group relative inline-flex items-center gap-2 overflow-hidden rounded-full bg-linear-to-r from-brand-600 via-brand-500 to-brand-700 px-5 py-2 text-sm font-semibold text-white shadow-lg shadow-brand-600/25 ring-2 ring-gold-300 ring-offset-2 ring-offset-ivory transition hover:-translate-y-0.5 hover:shadow-xl hover:shadow-brand-600/30">
                <span class="pointer-events-none absolute inset-y-0 -left-1/2 w-1/3 -skew-x-12 bg-white/25 transition-all duration-700 group-hover:left-full" aria-hidden="true"></span>
                <svg class="size-4 text-gold-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.8 5.6L19.5 9l-5.7 1.4L12 16l-1.8-5.6L4.5 9l5.7-1.4L12 2Zm7 11 .9 2.6 2.6.9-2.6.9L19 20l-.9-2.6-2.6-.9 2.6-.9L19 13ZM5 15l.7 1.8 1.8.7-1.8.7L5 20l-.7-1.8-1.8-.7 1.8-.7L5 15Z"/></svg>
                {{ $wedding->site?->is_published ? 'Kad digital saya' : 'Buat kad digital' }}
            </a>
        @endif
    </x-slot:actions>

    {{-- resources/js/components/customer/CustomerDashboardPage.vue --}}
    <div data-vue="customer-dashboard-page" data-props="@vueProps($props)"></div>

    {{-- The couple card stays server-rendered: it is the one piece of this page
         that shows another person's name, and it reads from relations the
         component would otherwise have to be handed wholesale. --}}
    @if ($wedding)
        <x-wedding-couple :wedding="$wedding" class="mt-6" />
    @endif
</x-layouts.customer>
