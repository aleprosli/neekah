@isset($chooser)
    <x-auth-card title="Daftar akaun Neekah" subtitle="Anda mendaftar sebagai siapa?" :card="false">
        {{-- resources/js/components/auth/AuthRoleChooser.vue --}}
        <div data-vue="auth-role-chooser" data-props="@vueProps($chooser)"></div>
    </x-auth-card>
@else
    <x-auth-card
        :title="$invitation ? 'Terima jemputan' : 'Daftar sebagai pengantin'"
        :subtitle="$invitation ? 'Daftar akaun untuk menyertai majlis ini. Anda akan terus dihubungkan selepas mendaftar.' : 'Percuma. Rancang majlis, urus bajet dan cari vendor di satu tempat.'"
        :audience="$audience"
        :switch-url="route('register')"
    >
        {{-- resources/js/components/auth/AuthForm.vue --}}
        <div data-vue="auth-form" data-props="@vueProps($props)"></div>
    </x-auth-card>
@endisset
