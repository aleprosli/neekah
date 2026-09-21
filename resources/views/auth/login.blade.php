@isset($chooser)
    <x-auth-card :title="__('auth_pages.login.chooser_title')" :subtitle="__('auth_pages.login.chooser_subtitle')" :card="false">
        {{-- resources/js/components/auth/AuthRoleChooser.vue --}}
        <div data-vue="auth-role-chooser" data-props="@vueProps($chooser)"></div>
    </x-auth-card>
@else
    <x-auth-card
        :title="$audience === App\Enums\AuthAudience::Vendor ? __('auth_pages.login.vendor_title') : __('auth_pages.login.title')"
        :subtitle="$audience === App\Enums\AuthAudience::Vendor ? __('auth_pages.login.vendor_subtitle') : __('auth_pages.login.subtitle')"
        :audience="$audience"
        :switch-url="route('login')"
    >
        {{-- resources/js/components/auth/AuthForm.vue --}}
        <div data-vue="auth-form" data-props="@vueProps($props)"></div>
    </x-auth-card>
@endisset
