<script setup>
/**
 * The budget: what the couple set aside per category against what they have
 * actually committed. The whole table is one form, so a round of planning is
 * one save rather than twelve.
 */
import { computed, ref } from 'vue';
import DataTable from '../ui/DataTable.vue';
import UiBarChart from '../ui/UiBarChart.vue';
import UiDonutChart from '../ui/UiDonutChart.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    stats: { type: Array, required: true },
    progress: { type: Object, required: true },
    charts: { type: Object, required: true },
    rows: { type: Array, required: true },
    totals: { type: Object, required: true },
    budget: { type: Number, required: true },
    action: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const COLUMNS = [
    { key: 'category', label: 'Kategori' },
    { key: 'planned', label: 'Bajet', align: 'right' },
    { key: 'actual', label: 'Actual', align: 'right' },
    { key: 'difference', label: 'Beza', align: 'right' },
    { key: 'vendors', label: 'Vendor' },
];

const planned = ref(Object.fromEntries(props.rows.map((row) => [row.id, row.planned])));
const total = ref(props.budget);

/** The allocation total updates as the couple types, before anything is saved. */
const allocated = computed(() => Object.values(planned.value).reduce((sum, value) => sum + Number(value || 0), 0));
const money = (value) => `RM${Math.round(value).toLocaleString('en-MY')}`;
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <div class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
        <div class="flex flex-wrap items-center justify-between gap-x-3 text-sm">
            <span class="font-medium">Penggunaan bajet</span>
            <span class="text-ink-muted">{{ progress.caption }}</span>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-muted">
            <div :class="['h-full rounded-full transition-all', progress.over ? 'bg-amber-500' : 'bg-brand-600']" :style="{ width: `${progress.percent}%` }"></div>
        </div>
        <p v-if="allocated > total" class="mt-2 text-xs text-amber-700">
            Agihan kategori anda melebihi jumlah bajet sebanyak {{ money(allocated - total) }}.
        </p>
    </div>

    <section class="mt-6 grid gap-4 lg:grid-cols-2">
        <div class="min-w-0 rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Komitmen mengikut bulan</h2>
            <p class="text-sm text-ink-muted">Jumlah tempahan yang anda buat setiap bulan.</p>
            <UiBarChart class="mt-4" :series="charts.committed" empty="Belum ada tempahan untuk dipaparkan." />
        </div>

        <div class="min-w-0 rounded-2xl border border-line bg-surface-raised p-5">
            <h2 class="font-semibold">Bayaran mengikut bulan</h2>
            <p class="text-sm text-ink-muted">Wang yang benar-benar sudah keluar.</p>
            <UiBarChart class="mt-4" :series="charts.paid" empty="Belum ada bayaran direkod." />
        </div>

        <div class="rounded-2xl border border-line bg-surface-raised p-5 lg:col-span-2">
            <h2 class="font-semibold">Perbelanjaan mengikut kategori</h2>
            <UiDonutChart class="mt-4" :series="charts.categories" empty="Tempah vendor untuk melihat agihan perbelanjaan anda." />
        </div>
    </section>

    <form :action="action" method="POST" class="mt-6">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="PUT">

        <DataTable :rows="rows" :columns="COLUMNS" :csrf="csrf">
            <template #cell-category="{ row }">
                <span class="flex items-center gap-2 whitespace-nowrap">
                    <img v-if="row.illustration" :src="row.illustration" alt="" class="size-5 shrink-0 object-contain mix-blend-multiply">
                    <span v-else aria-hidden="true">{{ row.icon }}</span>
                    {{ row.category }}
                </span>
            </template>

            <template #cell-planned="{ row }">
                <label class="sr-only" :for="`planned-${row.id}`">Bajet {{ row.category }}</label>
                <input
                    :id="`planned-${row.id}`"
                    v-model="planned[row.id]"
                    type="number"
                    step="50"
                    min="0"
                    :name="`planned[${row.id}]`"
                    class="w-28 rounded-lg border border-line bg-surface px-2 py-1.5 text-right focus:border-brand-400 focus:outline-none"
                >
            </template>

            <template #cell-actual="{ row }">
                <template v-if="row.has_bookings">
                    {{ row.actual }}
                    <span class="block text-xs text-ink-muted">dibayar {{ row.paid }}</span>
                </template>
                <span v-else class="text-ink-muted">—</span>
            </template>

            <template #cell-difference="{ row }">
                <span :class="['font-medium', row.has_bookings ? (row.over ? 'text-red-600' : 'text-emerald-600') : 'text-ink-muted']">
                    {{ row.has_bookings ? row.difference : '—' }}
                </span>
            </template>

            <template #cell-vendors="{ row }">
                <a v-for="booking in row.bookings" :key="booking.url" :href="booking.url" class="block truncate text-xs hover:text-brand-700">{{ booking.vendor }}</a>
                <a v-if="!row.bookings.length" :href="row.find_vendors_url" class="text-xs text-ink-muted hover:text-brand-700">Cari vendor →</a>
            </template>
        </DataTable>

        <dl class="mt-3 flex flex-wrap justify-end gap-x-8 gap-y-1 rounded-2xl bg-surface-muted px-5 py-3 text-sm font-semibold">
            <div class="flex gap-2"><dt>Diagih</dt><dd>{{ money(allocated) }}</dd></div>
            <div class="flex gap-2"><dt>Ditempah</dt><dd>{{ totals.actual }}</dd></div>
            <div class="flex gap-2"><dt>{{ totals.remaining_label }}</dt><dd :class="totals.over ? 'text-red-600' : 'text-emerald-600'">{{ totals.remaining }}</dd></div>
        </dl>

        <div class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <label class="flex flex-col gap-1.5 sm:w-56">
                <span class="text-sm font-medium">Jumlah bajet majlis (RM)</span>
                <input v-model="total" type="number" step="100" min="0" name="budget" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <span v-if="errors.budget" class="text-xs text-brand-700">{{ errors.budget }}</span>
            </label>

            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan bajet</button>
        </div>
    </form>
</template>
