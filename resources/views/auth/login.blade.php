@isset($chooser)
    <x-auth-card title="Log masuk ke Neekah" subtitle="Pilih cara anda nak teruskan." :card="false">
        {{-- resources/js/components/auth/AuthRoleChooser.vue --}}
        <div data-vue="auth-role-chooser" data-props="@vueProps($chooser)"></div>
    </x-auth-card>
@else
    <x-auth-card
        :title="$audience === App\Enums\AuthAudience::Vendor ? 'Log masuk vendor' : 'Log masuk'"
        :subtitle="$audience === App\Enums\AuthAudience::Vendor ? 'Urus tempahan, enquiry dan profil perniagaan anda.' : 'Selamat kembali. Urus majlis dan tempahan anda.'"
        :audience="$audience"
        :switch-url="route('login')"
    >
        {{-- resources/js/components/auth/AuthForm.vue --}}
        <div data-vue="auth-form" data-props="@vueProps($props)"></div>
    </x-auth-card>
@endisset
