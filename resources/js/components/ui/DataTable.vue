<script setup>
/**
 * One table for the whole application.
 *
 * Sorting, searching and paging happen on the server, because these lists grow
 * past what is sensible to ship to a browser. The component asks an endpoint
 * for a page of rows and renders whatever columns it was given, so a new table
 * is a controller method and a column list, not another table implementation.
 */
import { FlexRender, getCoreRowModel, useVueTable } from '@tanstack/vue-table';
import { computed, h, onMounted, ref, watch } from 'vue';

const props = defineProps({
    /** Endpoint returning { data: [], meta: { total, per_page, current_page, last_page } }. */
    dataUrl: { type: String, required: true },
    /** [{ key, label, sortable?, align?, type? }] — type "html" renders trusted markup from the server. */
    columns: { type: Array, required: true },
    searchPlaceholder: { type: String, default: 'Cari…' },
    emptyTitle: { type: String, default: 'Tiada rekod' },
    emptyMessage: { type: String, default: 'Tiada apa-apa untuk dipaparkan buat masa ini.' },
    initialSort: { type: String, default: '' },
    perPage: { type: Number, default: 15 },
});

const rows = ref([]);
const meta = ref({ total: 0, current_page: 1, last_page: 1 });
const loading = ref(true);
const failed = ref(false);
const search = ref('');
const sort = ref(props.initialSort);
const direction = ref('desc');
const page = ref(1);

const columnDefs = computed(() =>
    props.columns.map((column) => ({
        accessorKey: column.key,
        header: column.label,
        meta: column,
        cell: (info) =>
            column.type === 'html'
                ? h('span', { innerHTML: info.getValue() ?? '' })
                : (info.getValue() ?? '—'),
    })),
);

const table = useVueTable({
    get data() {
        return rows.value;
    },
    get columns() {
        return columnDefs.value;
    },
    getCoreRowModel: getCoreRowModel(),
});

const load = async () => {
    loading.value = true;
    failed.value = false;

    const query = new URLSearchParams({
        page: page.value,
        per_page: props.perPage,
        ...(search.value ? { search: search.value } : {}),
        ...(sort.value ? { sort: sort.value, direction: direction.value } : {}),
    });

    try {
        const response = await fetch(`${props.dataUrl}?${query}`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const payload = await response.json();
        rows.value = payload.data ?? [];
        meta.value = payload.meta ?? meta.value;
    } catch (problem) {
        failed.value = true;
        rows.value = [];
        console.error(problem);
    } finally {
        loading.value = false;
    }
};

/** A column may sort by a different database column than the one it shows. */
const sortKey = (column) => column.sort || column.key;

const sortBy = (column) => {
    if (!column.sortable) return;

    direction.value = sort.value === sortKey(column) && direction.value === 'asc' ? 'desc' : 'asc';
    sort.value = sortKey(column);
    page.value = 1;
    load();
};

/** A row that carries a url behaves like a link to it. */
const openRow = (row) => {
    if (row.url) window.location.href = row.url;
};

const go = (to) => {
    page.value = Math.min(Math.max(1, to), meta.value.last_page);
    load();
};

// One request after the vendor stops typing, not one per keystroke.
let debounce;
watch(search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
        page.value = 1;
        load();
    }, 300);
});

onMounted(load);
</script>

<template>
    <div class="flex min-w-0 flex-col gap-4">
        <div class="flex flex-wrap items-center gap-3">
            <label class="relative min-w-0 flex-1 sm:max-w-xs">
                <span class="sr-only">{{ searchPlaceholder }}</span>
                <input
                    v-model="search"
                    type="search"
                    :placeholder="searchPlaceholder"
                    class="w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none"
                >
            </label>
            <p class="text-xs text-ink-muted" aria-live="polite">
                <span v-if="loading">Memuatkan…</span>
                <span v-else>{{ meta.total }} rekod</span>
            </p>
            <slot name="actions" />
        </div>

        <p v-if="failed" class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">
            Senarai tidak dapat dimuatkan. Muat semula halaman untuk cuba lagi.
        </p>

        <div class="min-w-0 overflow-x-auto rounded-2xl border border-line">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-line bg-surface-muted/60">
                    <tr>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            scope="col"
                            :class="['px-4 py-3 font-medium whitespace-nowrap', header.column.columnDef.meta.align === 'right' ? 'text-right' : '']"
                        >
                            <button
                                v-if="header.column.columnDef.meta.sortable"
                                type="button"
                                class="inline-flex items-center gap-1 transition hover:text-brand-700"
                                @click="sortBy(header.column.columnDef.meta)"
                            >
                                {{ header.column.columnDef.header }}
                                <span aria-hidden="true" class="text-[10px]">{{ sort === sortKey(header.column.columnDef.meta) ? (direction === 'asc' ? '▲' : '▼') : '↕' }}</span>
                            </button>
                            <span v-else>{{ header.column.columnDef.header }}</span>
                        </th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-line">
                    <tr v-if="loading && !rows.length">
                        <td :colspan="columns.length" class="px-4 py-10 text-center text-ink-muted">Memuatkan…</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td :colspan="columns.length" class="px-4 py-12 text-center">
                            <p class="font-medium">{{ emptyTitle }}</p>
                            <p class="mt-1 text-ink-muted">{{ emptyMessage }}</p>
                        </td>
                    </tr>
                    <tr
                        v-for="row in table.getRowModel().rows"
                        :key="row.id"
                        :class="['transition hover:bg-surface-muted/60', row.original.url ? 'cursor-pointer' : '']"
                        @click="openRow(row.original)"
                    >
                        <td
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            :class="['px-4 py-3 align-middle', cell.column.columnDef.meta.align === 'right' ? 'text-right' : '']"
                        >
                            <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="meta.last_page > 1" class="flex flex-wrap items-center justify-between gap-3">
            <p class="text-xs text-ink-muted">Halaman {{ meta.current_page }} daripada {{ meta.last_page }}</p>
            <div class="flex gap-2">
                <button type="button" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 disabled:opacity-40" :disabled="meta.current_page <= 1" @click="go(meta.current_page - 1)">Sebelum</button>
                <button type="button" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" @click="go(meta.current_page + 1)">Seterusnya</button>
            </div>
        </div>
    </div>
</template>
