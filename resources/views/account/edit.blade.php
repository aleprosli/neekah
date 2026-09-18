@php
    $layout = 'layouts.'.match (true) {
        $user->isAdmin() => 'admin',
        $user->isVendor() => 'vendor',
        default => 'customer',
    };
@endphp

<x-dynamic-component :component="$layout" title="Akaun saya" heading="Akaun saya" subheading="Maklumat peribadi dan kata laluan anda.">
    {{-- resources/js/components/account/AccountSettingsPage.vue --}}
    <div data-vue="account-settings-page" data-props="@vueProps($props)"></div>
</x-dynamic-component>
