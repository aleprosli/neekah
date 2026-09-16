<x-layouts.app title="Jemputan majlis">
    <x-site.header />

    <main class="mx-auto flex min-h-[70vh] max-w-lg flex-col justify-center px-4 pt-24 pb-16 sm:px-6">
        {{-- resources/js/components/public/InvitationPage.vue --}}
        <div data-vue="invitation-page" data-props="@vueProps($props)"></div>
    </main>

    <x-site.footer />
</x-layouts.app>
