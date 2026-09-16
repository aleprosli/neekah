<x-layouts.admin :title="$vendor->name" :heading="$vendor->name" :subheading="$vendor->category->name.' · '.$vendor->city.', '.$vendor->state">
    <x-slot:actions>
        @if ($vendor->isApproved())
            <a href="{{ route('vendors.show', $vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Lihat profil awam</a>
        @endif
        <x-confirm-action
            :action="route('admin.users.impersonate', $vendor->user)"
            :title="'Log masuk sebagai '.$vendor->user->name.'?'"
            message="Anda akan melihat dashboard vendor ini persis seperti pemiliknya. Pembayaran dimatikan, dan tindakan ini direkod dalam log sistem."
            confirm="Ya, impersonate"
            trigger-class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700"
        >Impersonate pemilik</x-confirm-action>
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminVendorDetail.vue --}}
    <div data-vue="admin-vendor-detail" data-props="{{ json_encode($props) }}"></div>
</x-layouts.admin>
