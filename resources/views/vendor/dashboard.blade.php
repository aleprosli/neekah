{{-- The vendor's home, laid out like the couple's: one burgundy card at the
     top (their rank, who they are, their plan, three numbers), then what
     needs doing on the left and how to climb the ranking on the right. --}}
<x-layouts.vendor :title="__('pages.dash.dashboard_vendor')">
    <div class="flex flex-col gap-6">
        <x-vendor-hero :vendor="$vendor" :props="$props" />

        <div class="grid gap-6 lg:grid-cols-12 lg:items-start">
            {{-- resources/js/components/vendor/VendorDashboardPage.vue --}}
            <div class="min-w-0 lg:col-span-8" data-vue="vendor-dashboard-page" data-props="@vueProps($props)"></div>

            <aside id="ranking" class="min-w-0 scroll-mt-24 lg:sticky lg:top-24 lg:col-span-4">
                <x-vendor-ranking :vendor="$vendor" />
            </aside>
        </div>
    </div>
</x-layouts.vendor>
