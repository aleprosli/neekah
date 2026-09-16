<x-auth-card
    :title="$invitation ? 'Terima jemputan' : 'Daftar akaun'"
    :subtitle="$invitation ? 'Daftar akaun untuk menyertai majlis ini. Anda akan terus dihubungkan selepas mendaftar.' : 'Untuk pengantin. Vendor boleh mendaftar melalui halaman Jadi Vendor.'"
>
    {{-- resources/js/components/auth/AuthForm.vue --}}
    <div data-vue="auth-form" data-props="@vueProps($props)"></div>
</x-auth-card>
