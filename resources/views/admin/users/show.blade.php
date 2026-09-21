<x-layouts.admin :title="$user->name" :heading="$user->name" :subheading="$user->role->label().' · '.$user->email">
    <x-slot:actions>
        @if ($user->vendor)
            <a href="{{ route('admin.vendors.show', $user->vendor) }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.dash.profil_vendor') }}</a>
        @endif
        @if ($user->canBeImpersonated())
            <x-confirm-action
                :action="route('admin.users.impersonate', $user)"
                :title="'Log masuk sebagai '.$user->name.'?'"
                :message="__('pages.dash.anda_akan_melihat_neekah_persis')"
                :confirm="__('pages.dash.ya_impersonate')"
                trigger-class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700"
            >{{ __('pages.dash.impersonate') }}</x-confirm-action>
        @endif
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminUserDetail.vue --}}
    <div data-vue="admin-user-detail" data-props="@vueProps($props)"></div>
</x-layouts.admin>
