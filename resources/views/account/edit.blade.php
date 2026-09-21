@php
    $layout = 'layouts.'.match (true) {
        $user->isAdmin() => 'admin',
        $user->isVendor() => 'vendor',
        default => 'customer',
    };
@endphp

<x-dynamic-component :component="$layout" :title="__('pages.dash.akaun_saya')" :heading="__('pages.dash.akaun_saya_2')" :subheading="__('pages.dash.maklumat_peribadi_dan_kata_laluan')">
    {{-- resources/js/components/account/AccountSettingsPage.vue --}}
    <div data-vue="account-settings-page" data-props="@vueProps($props)"></div>
</x-dynamic-component>
