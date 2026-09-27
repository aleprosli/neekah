@if ($editing)
    <x-layouts.customer :title="__('pages.dash.edit_majlis')" :heading="__('pages.dash.edit_majlis')" :subheading="__('pages.dash.maklumat_ini_digunakan_untuk_cadangan')">
        {{-- resources/js/components/customer/CustomerWeddingForm.vue --}}
        <div data-vue="customer-wedding-form" data-props="@vueProps($props)"></div>
    </x-layouts.customer>
@else
    {{-- The first thing a new couple does on Neekah, so it opens like a card
         rather than a form: a welcome in the display serif, the florals of the
         invitation designs, and a plain promise of what comes next. --}}
    <x-layouts.customer :title="__('pages.dash.cipta_wedding_project')">
        <section class="relative mb-8 overflow-hidden rounded-3xl border border-line bg-surface-raised px-6 py-8 shadow-sm sm:px-10 sm:py-10">
            <x-site.ornament name="corner-peony" class="absolute -top-12 -right-12 size-44 opacity-40 sm:size-64" color="var(--color-brand-200)" color2="var(--color-gold-300)" />
            <x-site.ornament name="corner-wildflower" class="absolute -bottom-14 -left-14 size-40 rotate-180 opacity-25 sm:size-52" color="var(--color-gold-300)" color2="var(--color-brand-100)" />

            <div class="relative max-w-2xl">
                <p class="font-display text-sm font-semibold tracking-wide text-gold-600">{{ __('pages.wedding_create.eyebrow', ['name' => App\Support\CallingName::from(auth()->user()->name)]) }}</p>
                <h1 class="mt-2 font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl">{{ __('pages.wedding_create.title') }}</h1>
                <p class="mt-3 text-sm leading-relaxed text-ink-muted sm:text-base">{{ __('pages.wedding_create.intro') }}</p>
                <x-site.ornament name="divider-floral" class="mt-5 h-5 w-36" color="var(--color-gold-500)" color2="var(--color-gold-300)" />
            </div>
        </section>

        {{-- resources/js/components/customer/CustomerWeddingForm.vue --}}
        <div data-vue="customer-wedding-form" data-props="@vueProps($props)"></div>
    </x-layouts.customer>
@endif
