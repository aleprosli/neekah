<x-layouts.customer title="Majlis saya" :heading="$wedding?->title ?? 'Majlis saya'" :subheading="$wedding ? $wedding->event_date->translatedFormat('l, j F Y').' · '.$wedding->city.', '.$wedding->state : 'Cipta wedding project untuk mula merancang.'">
    <x-slot:actions>
        @if ($wedding)
            <a href="{{ route('weddings.edit', $wedding) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Edit majlis</a>
        @endif
        @if (auth()->user()->canBecomeVendor())
            <a href="{{ route('vendor.convert') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Saya vendor</a>
        @endif
        <a href="{{ route('vendors.index') }}" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">Cari vendor</a>
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
