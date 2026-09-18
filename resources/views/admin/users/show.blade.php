<x-layouts.admin :title="$user->name" :heading="$user->name" :subheading="$user->role->label().' · '.$user->email">
    <x-slot:actions>
        @if ($user->vendor)
            <a href="{{ route('admin.vendors.show', $user->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Profil vendor</a>
        @endif
        @if ($user->canBeImpersonated())
            <x-confirm-action
                :action="route('admin.users.impersonate', $user)"
                :title="'Log masuk sebagai '.$user->name.'?'"
                message="Anda akan melihat Neekah persis seperti pengguna ini. Pembayaran dimatikan, dan tindakan ini direkod dalam log sistem."
                confirm="Ya, impersonate"
                trigger-class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700"
            >Impersonate</x-confirm-action>
        @endif
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminUserDetail.vue --}}
    <div data-vue="admin-user-detail" data-props="@vueProps($props)"></div>
</x-layouts.admin>
