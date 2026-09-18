@php $audience = App\Enums\AuthAudience::Vendor; @endphp

{{-- Sign-up furniture, like login and the couple's form, but not noindex:
     this is also the page vendors find from Google, and the controller gives
     it a title and description of its own. The form draws its own cards. --}}
<x-layouts.auth title="Daftar sebagai vendor" wide>
    <div class="text-center">
        <p class="mb-4 inline-flex items-center gap-2 rounded-full border border-line bg-surface-raised px-3 py-1 text-xs font-medium text-ink-muted">
            <x-nav-icon :name="$audience->icon()" />
            {{ $audience->label() }}
            <span class="text-line" aria-hidden="true">·</span>
            <a href="{{ route('register') }}" class="font-semibold text-brand-600 hover:underline">Tukar</a>
        </p>

        <h1 class="font-display text-2xl font-semibold tracking-tight sm:text-3xl">Sertai Neekah sebagai vendor</h1>
        <p class="mx-auto mt-2 max-w-xl text-sm text-ink-muted">Daftar percuma. Selepas admin meluluskan profil anda, perniagaan anda akan dipaparkan di marketplace dan pengantin boleh tempah terus.</p>
    </div>

    {{-- resources/js/components/vendor/VendorRegisterForm.vue --}}
    <div data-vue="vendor-register-form" data-props="@vueProps($props)"></div>
</x-layouts.auth>
