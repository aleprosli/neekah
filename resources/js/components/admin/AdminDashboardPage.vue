<script setup>
/** The platform at a glance: the numbers, what needs approving, what just sold. */
import DataTable from '../ui/DataTable.vue';
import UiBadge from '../ui/UiBadge.vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const BOOKING_COLUMNS = [
    { key: 'reference', label: 'Rujukan' },
    { key: 'vendor', label: 'Vendor' },
    { key: 'customer', label: 'Pengantin' },
    { key: 'total', label: 'Jumlah', align: 'right' },
    { key: 'commission', label: 'Komisen', align: 'right' },
    { key: 'status_label', label: 'Status' },
];

defineProps({
    stats: { type: Array, required: true },
    alert: { type: Object, default: null },
    pending: { type: Array, required: true },
    pendingUrl: { type: String, required: true },
    topVendors: { type: Array, required: true },
    recentBookings: { type: Array, required: true },
    bookingsUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <a v-if="alert" :href="alert.url" class="mt-4 flex items-center gap-3 rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm text-amber-900 transition hover:border-amber-400">
        <span class="text-lg">⚠️</span>
        <span><strong>{{ alert.count }}</strong> laporan vendor menunggu semakan anda.</span>
    </a>

    <!-- min-w-0 on each grid child: without it a long business name widens the
         column past the screen and zooms the whole page out on a phone. -->
    <div class="mt-8 grid gap-8 lg:grid-cols-2">
        <section class="flex min-w-0 flex-col gap-4">
            <div class="flex items-center justify-between">
                <h2 class="font-display text-xl font-semibold">{{ $t('admin_dashboard.menunggu_kelulusan') }}</h2>
                <a :href="pendingUrl" class="text-sm font-medium text-brand-600 hover:underline">{{ $t('admin_dashboard.semua') }}</a>
            </div>

            <p v-if="!pending.length" class="rounded-2xl border border-dashed border-line p-6 text-sm text-ink-muted">{{ $t('admin_dashboard.tiada_permohonan_vendor_baharu') }}</p>

            <ul v-else class="min-w-0 divide-y divide-line rounded-2xl border border-line">
                <li v-for="vendor in pending" :key="vendor.id" class="flex items-center gap-3 p-4">
                    <span :class="['flex size-10 shrink-0 items-center justify-center rounded-xl bg-linear-to-br p-1 text-lg', vendor.tone]">
                        <img v-if="vendor.illustration" :src="vendor.illustration" alt="" class="size-full object-contain mix-blend-multiply">
                        <template v-else>{{ vendor.icon }}</template>
                    </span>

                    <div class="min-w-0 flex-1">
                        <a :href="vendor.url" class="block truncate font-medium hover:text-brand-700">{{ vendor.name }}</a>
                        <p class="truncate text-xs text-ink-muted">{{ vendor.summary }}</p>
                    </div>

                    <!-- Approving emails the vendor and puts them on the
                         marketplace, so it asks before it does that. -->
                    <UiConfirm
                        :action="vendor.approve_url"
                        :fields="{ status: 'approved' }"
                        :title="`Luluskan ${vendor.name}?`"
                        :message="$t('admin_dashboard.profil_ini_akan_dipaparkan_di')"
                        confirm-:label="$t('admin_dashboard.ya_luluskan')"
                        trigger-class="shrink-0 rounded-full bg-brand-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-brand-700"
                        :csrf="csrf"
                    >{{ $t('admin_dashboard.lulus') }}</UiConfirm>
                </li>
            </ul>
        </section>

        <section class="flex min-w-0 flex-col gap-4">
            <h2 class="font-display text-xl font-semibold">{{ $t('admin_dashboard.vendor_teratas') }}</h2>
            <ol class="min-w-0 divide-y divide-line rounded-2xl border border-line">
                <li v-for="(vendor, at) in topVendors" :key="vendor.id" class="flex items-center gap-3 p-4 text-sm">
                    <span class="w-5 shrink-0 text-center font-semibold text-ink-muted">{{ at + 1 }}</span>
                    <div class="min-w-0 flex-1">
                        <a :href="vendor.url" class="block truncate font-medium hover:text-brand-700">{{ vendor.name }}</a>
                        <p class="truncate text-xs text-ink-muted">{{ vendor.summary }}</p>
                    </div>
                    <span class="font-display font-semibold">{{ vendor.score }}</span>
                </li>
            </ol>
        </section>
    </div>

    <section class="mt-8 flex min-w-0 flex-col gap-4">
        <div class="flex items-center justify-between">
            <h2 class="font-display text-xl font-semibold">{{ $t('admin_dashboard.tempahan_terkini') }}</h2>
            <a :href="bookingsUrl" class="text-sm font-medium text-brand-600 hover:underline">{{ $t('admin_dashboard.semua_2') }}</a>
        </div>

        <DataTable :rows="recentBookings" :columns="BOOKING_COLUMNS" :csrf="csrf">
            <template #cell-reference="{ row }">
                <a :href="row.url" class="font-medium hover:text-brand-700">{{ row.reference }}</a>
            </template>
            <template #cell-status_label="{ row }">
                <UiBadge :label="row.status_label" :tone="row.status_tone" />
            </template>
        </DataTable>
    </section>
</template>
