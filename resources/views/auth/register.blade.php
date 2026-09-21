@isset($chooser)
    <x-auth-card :title="__('auth_pages.register.chooser_title')" :subtitle="__('auth_pages.register.chooser_subtitle')" :card="false">
        {{-- resources/js/components/auth/AuthRoleChooser.vue --}}
        <div data-vue="auth-role-chooser" data-props="@vueProps($chooser)"></div>
    </x-auth-card>
@else
    <x-auth-card
        :title="$invitation ? __('auth_pages.register.accept_title') : __('auth_pages.register.couple_title')"
        :subtitle="$invitation ? __('auth_pages.register.accept_subtitle') : __('auth_pages.register.couple_subtitle')"
        :audience="$audience"
        :switch-url="route('register')"
    >
        {{-- resources/js/components/auth/AuthForm.vue --}}
        <div data-vue="auth-form" data-props="@vueProps($props)"></div>
    </x-auth-card>
@endisset
