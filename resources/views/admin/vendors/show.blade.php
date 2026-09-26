<x-layouts.admin :title="$vendor->name" :heading="$vendor->name" :subheading="$vendor->category->name.' · '.$vendor->city.', '.$vendor->state">
    <x-slot:actions>
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.lihat_profil_awam') }}</a>
        @endif
        <x-confirm-action
            :action="route('admin.users.impersonate', $vendor->user)"
            :title="'Log masuk sebagai '.$vendor->user->name.'?'"
            :message="__('pages.dash.anda_akan_melihat_dashboard_vendor')"
            :confirm="__('pages.dash.ya_impersonate')"
            trigger-class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700"
        >{{ __('pages.dash.impersonate_pemilik') }}</x-confirm-action>
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminVendorDetail.vue --}}
    <div data-vue="admin-vendor-detail" data-props="@vueProps($props)"></div>

    @include('admin.vendors.partials.booking')
</x-layouts.admin>
