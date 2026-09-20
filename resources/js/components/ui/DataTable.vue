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
import UiConfirm from './UiConfirm.vue';

const props = defineProps({
    /**
     * Endpoint returning { data: [], meta: { total, per_page, current_page, last_page } }.
     * Leave it out and pass `rows` instead for a list that is already in hand.
     */
    dataUrl: { type: String, default: null },
    /** Rows to show as they are, for a table that does not page or search. */
    rows: { type: Array, default: null },
    /** [{ key, label, sortable?, align?, type? }] — type "html" renders trusted markup from the server. */
    columns: { type: Array, required: true },
    /**
     * Chip filters shown above the table, each group
     * { key, label?, value, allLabel?, options: [{ value, label, count?, description? }] }.
     * Choosing one reloads the rows in place; the groups are mutually
     * exclusive, because a server that reads two of them at once has to pick.
     */
    filters: { type: Array, default: () => [] },
    searchPlaceholder: { type: String, default: 'Cari…' },
    emptyTitle: { type: String, default: 'Tiada rekod' },
    emptyMessage: { type: String, default: 'Tiada apa-apa untuk dipaparkan buat masa ini.' },
    initialSort: { type: String, default: '' },
    perPage: { type: Number, default: 15 },
    /**
     * An action offered on every row that carries the url it posts to:
     * { urlKey, label, title, message, confirmLabel, method, tone }.
     */
    rowAction: { type: Object, default: null },
    /**
     * Actions over the rows the admin ticked:
     * [{ key, label, tone, url, method?, fields?, confirm: { title, message, confirmLabel, tone } }].
     * __COUNT__ in the confirm wording is replaced with how many are ticked.
     * Rows must carry an `id` for this to have anything to post.
     */
    bulkActions: { type: Array, default: () => [] },
    csrf: { type: String, default: '' },
});

const fetched = ref([]);
const serverColumns = ref(null);

/** A filtered list may come back with its own columns (the segment views do). */
const activeColumns = computed(() => serverColumns.value ?? props.columns);

const rows = computed(() => props.rows ?? fetched.value);

/**
 * A row may carry one action or several. A hidden review, for instance, offers
 * both putting it back and deleting it for good.
 */
const actionsFor = (row) => row.actions ?? (row.action ? [row.action] : []);
const isStatic = computed(() => props.rows !== null);
const meta = ref({ total: 0, current_page: 1, last_page: 1 });
const loading = ref(false);
const failed = ref(false);
const search = ref('');
const sort = ref(props.initialSort);
const direction = ref('desc');
const page = ref(1);

const selected = ref(Object.fromEntries(props.filters.map((group) => [group.key, group.value ?? ''])));

/** One group at a time: picking a segment drops the role, as the server does. */
const chooseFilter = (group, value) => {
    Object.keys(selected.value).forEach((key) => {
        selected.value[key] = key === group.key ? value : '';
    });
    page.value = 1;
    load();
    rememberFilters();
};

const activeOption = (group) => group.options.find((option) => option.value === selected.value[group.key]);

/** Keep the address bar in step, so a refresh or a shared link filters too. */
const rememberFilters = () => {
    const url = new URL(window.location.href);
    props.filters.forEach((group) => {
        const value = selected.value[group.key];
        if (value) {
            url.searchParams.set(group.key, value);
        } else {
            url.searchParams.delete(group.key);
        }
    });
    window.history.replaceState({}, '', url);
};

const columnDefs = computed(() =>
    activeColumns.value.map((column) => ({
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
    if (isStatic.value) return;

    loading.value = true;
    failed.value = false;

    // The endpoint may already carry a query of its own, so the parameters are
    // merged into it. Appending "?page=2" to a url that has one makes the
    // server read "status=pending?page=2": no filter, and always page one.
    const url = new URL(props.dataUrl, window.location.origin);
    const query = {
        page: page.value,
        per_page: props.perPage,
        ...Object.fromEntries(Object.entries(selected.value).filter(([, value]) => value)),
        ...(search.value ? { search: search.value } : {}),
        ...(sort.value ? { sort: sort.value, direction: direction.value } : {}),
    };
    Object.entries(query).forEach(([key, value]) => url.searchParams.set(key, value));
    props.filters.forEach((group) => {
        if (!selected.value[group.key]) {
            url.searchParams.delete(group.key);
        }
    });

    try {
        const response = await fetch(url, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);

        const payload = await response.json();
        fetched.value = payload.data ?? [];
        // The ticks belong to rows that are no longer on screen.
        picked.value = [];
        meta.value = payload.meta ?? meta.value;
        serverColumns.value = payload.columns ?? null;
    } catch (problem) {
        failed.value = true;
        fetched.value = [];
        console.error(problem);
    } finally {
        loading.value = false;
    }
};

/** A column may sort by a different database column than the one it shows. */
const sortKey = (column) => column.sort || column.key;

/** The card layout has no headers to click, so it sorts from a select. */
const sortFromSelect = (value) => {
    const [key, chosen] = value.split(':');
    sort.value = key;
    direction.value = chosen;
    page.value = 1;
    load();
};

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

const cardColumns = computed(() => {
    const last = activeColumns.value.at(-1);

    return activeColumns.value.filter(
        (column, at) => at !== 0 && !(column === last && last.type === 'html'),
    );
});

const sortableColumns = computed(() => activeColumns.value.filter((column) => column.sortable));

/**
 * Ticking rows for a batch action. A new marketplace arrives with dozens of
 * registrations, and approving them one dialog at a time is the whole evening.
 */
const picked = ref([]);
const selectable = computed(() => props.bulkActions.length > 0);
const pageIds = computed(() => rows.value.map((row) => row.id).filter((id) => id !== undefined && id !== null));
const allPagePicked = computed(() => pageIds.value.length > 0 && pageIds.value.every((id) => picked.value.includes(id)));

const togglePick = (id) => {
    picked.value = picked.value.includes(id) ? picked.value.filter((one) => one !== id) : [...picked.value, id];
};

const togglePage = () => {
    picked.value = allPagePicked.value
        ? picked.value.filter((id) => !pageIds.value.includes(id))
        : [...new Set([...picked.value, ...pageIds.value])];
};

/** __COUNT__ reads as the number ticked, so the dialog says what it will do. */
const withCount = (text) => (text ?? '').replaceAll('__COUNT__', picked.value.length);

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
        <!-- Filters swap the rows in place. They were links that reloaded the
             page, and the reload carried the filter in the endpoint's own
             query, where it collided with paging. -->
        <div v-for="group in filters" :key="group.key" class="flex min-w-0 flex-col gap-2">
            <p v-if="group.label" class="font-display text-xs tracking-[0.18em] text-gold uppercase">{{ group.label }}</p>
            <div class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:flex-wrap lg:px-0">
                <button
                    type="button"
                    :class="[
                        'shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap transition',
                        selected[group.key] ? 'border-line hover:border-brand-400' : 'border-brand-600 bg-brand-600 text-white',
                    ]"
                    :aria-pressed="!selected[group.key]"
                    @click="chooseFilter(group, '')"
                >{{ group.allLabel || 'Semua' }}</button>

                <button
                    v-for="option in group.options"
                    :key="option.value"
                    type="button"
                    :title="option.description"
                    :class="[
                        'flex shrink-0 items-center gap-2 rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap transition',
                        selected[group.key] === option.value ? 'border-brand-600 bg-brand-600 text-white' : 'border-line hover:border-brand-400',
                    ]"
                    :aria-pressed="selected[group.key] === option.value"
                    @click="chooseFilter(group, option.value)"
                >
                    {{ option.label }}
                    <span
                        v-if="option.count !== undefined && option.count !== null"
                        :class="[
                            'rounded-full px-2 py-0.5 text-xs font-semibold tabular-nums',
                            selected[group.key] === option.value ? 'bg-white/20' : 'bg-surface-muted text-ink-muted',
                        ]"
                    >{{ option.count }}</span>
                </button>
            </div>
            <p v-if="group.hint || activeOption(group)?.description" class="text-sm text-ink-muted">
                {{ activeOption(group)?.description || group.hint }}
            </p>
        </div>

        <div v-if="!isStatic || $slots.actions" class="flex flex-wrap items-center gap-3">
            <label v-if="!isStatic" class="relative min-w-0 flex-1 sm:max-w-xs">
                <span class="sr-only">{{ searchPlaceholder }}</span>
                <input
                    v-model="search"
                    type="search"
                    :placeholder="searchPlaceholder"
                    class="w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none"
                >
            </label>
            <!-- w-full, because a select is otherwise as wide as its longest
                 option, and "Nama perniagaan ↓" is wider than a phone. -->
            <label v-if="sortableColumns.length && !isStatic" class="min-w-0 flex-1 md:hidden">
                <span class="sr-only">Susun ikut</span>
                <select
                    class="nk-select w-full rounded-xl border border-line bg-surface px-3 py-2.5 pr-9 text-sm focus:border-brand-400 focus:outline-none"
                    :value="`${sort}:${direction}`"
                    @change="sortFromSelect($event.target.value)"
                >
                    <option v-for="column in sortableColumns" :key="column.key" :value="`${sortKey(column)}:desc`">{{ column.label }} ↓</option>
                    <option v-for="column in sortableColumns" :key="`${column.key}-asc`" :value="`${sortKey(column)}:asc`">{{ column.label }} ↑</option>
                </select>
            </label>

            <p v-if="!isStatic" class="text-xs text-ink-muted" aria-live="polite">
                <span v-if="loading">Memuatkan…</span>
                <span v-else>{{ meta.total }} rekod</span>
            </p>
            <slot name="actions" />
        </div>

        <!-- What the ticked rows can be done to, shown only once something is
             ticked so it never sits in the way. -->
        <div v-if="selectable && picked.length" class="sticky top-3 z-20 flex flex-wrap items-center gap-3 rounded-2xl border border-brand-300 bg-brand-50 px-4 py-3 shadow-sm">
            <p class="text-sm font-semibold text-brand-900">{{ picked.length }} dipilih</p>
            <div class="flex flex-wrap gap-2">
                <UiConfirm
                    v-for="action in bulkActions"
                    :key="action.key"
                    :action="action.url"
                    :method="action.method || 'POST'"
                    :fields="{ ...(action.fields || {}), ids: picked }"
                    :tone="action.confirm?.tone || action.tone || 'brand'"
                    :title="withCount(action.confirm?.title || `${action.label} __COUNT__ rekod?`)"
                    :message="withCount(action.confirm?.message)"
                    :confirm-label="withCount(action.confirm?.confirmLabel || action.label)"
                    :trigger-class="[
                        'rounded-full px-4 py-2 text-xs font-semibold transition',
                        action.tone === 'danger'
                            ? 'bg-red-600 text-white hover:bg-red-700'
                            : action.tone === 'line'
                              ? 'border border-line bg-surface font-medium hover:border-brand-400'
                              : 'bg-brand-600 text-white hover:bg-brand-700',
                    ].join(' ')"
                    :csrf="csrf"
                >{{ action.label }}</UiConfirm>
            </div>
            <button type="button" class="ml-auto text-xs font-medium text-ink-muted underline underline-offset-4 hover:text-ink" @click="picked = []">Kosongkan pilihan</button>
        </div>

        <p v-if="failed" class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">
            Senarai tidak dapat dimuatkan. Muat semula halaman untuk cuba lagi.
        </p>

        <!-- Cards on a phone, a table from md up: a row with seven columns is
             unreadable on a 390px screen however far it scrolls. -->
        <!-- min-w-0 the whole way down: a flex child sizes to its content by
             default, so one long business name pushes the card past the screen. -->
        <ul v-if="rows.length" class="flex min-w-0 flex-col gap-3 md:hidden">
            <li v-for="row in table.getRowModel().rows" :key="`card-${row.id}`" class="min-w-0 rounded-2xl border border-line bg-surface-raised p-4">
                <label v-if="selectable && row.original.id" class="mb-3 flex items-center gap-2 text-xs font-medium text-ink-muted">
                    <input type="checkbox" class="size-4 accent-brand-600" :checked="picked.includes(row.original.id)" @change="togglePick(row.original.id)">
                    Pilih
                </label>
                <component
                    :is="row.original.url ? 'a' : 'div'"
                    :href="row.original.url"
                    class="flex min-w-0 flex-col gap-2"
                >
                    <div class="flex min-w-0 items-start justify-between gap-3">
                        <p class="min-w-0 font-medium break-words">
                            <slot :name="`cell-${activeColumns[0].key}`" :row="row.original">
                                <span v-if="activeColumns[0].type === 'html'" v-html="row.original[activeColumns[0].key]"></span>
                                <span v-else>{{ row.original[activeColumns[0].key] }}</span>
                            </slot>
                        </p>
                        <span v-if="activeColumns.at(-1).type === 'html'" class="shrink-0" v-html="row.original[activeColumns.at(-1).key]"></span>
                    </div>

                    <dl class="flex min-w-0 flex-col gap-1 text-sm">
                        <div v-for="column in cardColumns" :key="column.key" class="flex min-w-0 justify-between gap-3">
                            <dt class="shrink-0 text-ink-muted">{{ column.label }}</dt>
                            <dd class="min-w-0 truncate text-right">
                                <slot :name="`cell-${column.key}`" :row="row.original">
                                    <span v-if="column.type === 'html'" v-html="row.original[column.key]"></span>
                                    <span v-else>{{ row.original[column.key] ?? '—' }}</span>
                                </slot>
                            </dd>
                        </div>
                    </dl>
                </component>

                <div v-if="rowAction || $slots.action" class="mt-3 flex items-center justify-end gap-3 border-t border-line pt-3">
                    <slot name="action" :row="row.original" />
                    <UiConfirm
                        v-for="(rowItem, at) in (rowAction?.inline ? actionsFor(row.original) : [])"
                        :key="at"
                        :action="rowItem.url"
                        :method="rowItem.method || 'POST'"
                        :fields="rowItem.fields || {}"
                        :tone="rowItem.confirm?.tone || 'brand'"
                        :title="rowItem.confirm?.title || `${rowItem.label}?`"
                        :message="rowItem.confirm?.message"
                        :confirm-label="rowItem.confirm?.confirmLabel || rowItem.label"
                        :trigger-class="[
                            'rounded-full px-4 py-2 text-xs font-semibold transition',
                            rowItem.tone === 'brand' ? 'bg-brand-600 text-white' : 'border border-line font-medium',
                        ].join(' ')"
                        :csrf="csrf"
                    >{{ rowItem.label }}</UiConfirm>

                    <UiConfirm
                        v-if="rowAction && !rowAction.inline && row.original[rowAction.urlKey]"
                        :action="row.original[rowAction.urlKey]"
                        :method="rowAction.method || 'POST'"
                        :tone="rowAction.tone || 'brand'"
                        :title="rowAction.title.replace('__ROW__', row.original[rowAction.labelKey] || '')"
                        :message="rowAction.message"
                        :confirm-label="rowAction.confirmLabel"
                        trigger-class="rounded-full border border-line px-4 py-2 text-xs font-medium transition hover:border-brand-400"
                        :csrf="csrf"
                    >{{ rowAction.label }}</UiConfirm>
                </div>
            </li>
        </ul>

        <p v-else-if="loading" class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted md:hidden">Memuatkan…</p>

        <div v-else class="rounded-2xl border border-dashed border-line p-8 text-center md:hidden">
            <p class="font-medium">{{ emptyTitle }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ emptyMessage }}</p>
        </div>

        <div class="hidden min-w-0 overflow-x-auto rounded-2xl border border-line md:block">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-line bg-surface-muted/60">
                    <tr>
                        <th v-if="selectable" scope="col" class="w-10 px-4 py-3">
                            <input
                                type="checkbox"
                                class="size-4 accent-brand-600"
                                :checked="allPagePicked"
                                :disabled="!pageIds.length"
                                aria-label="Pilih semua di halaman ini"
                                @change="togglePage"
                            >
                        </th>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            scope="col"
                            :class="['px-4 py-3 font-medium whitespace-nowrap', header.column.columnDef.meta.align === 'right' ? 'text-right' : '']"
                        >
                            <button
                                v-if="header.column.columnDef.meta.sortable && !isStatic"
                                type="button"
                                class="inline-flex items-center gap-1 transition hover:text-brand-700"
                                @click="sortBy(header.column.columnDef.meta)"
                            >
                                {{ header.column.columnDef.header }}
                                <span aria-hidden="true" class="text-[10px]">{{ sort === sortKey(header.column.columnDef.meta) ? (direction === 'asc' ? '▲' : '▼') : '↕' }}</span>
                            </button>
                            <span v-else>{{ header.column.columnDef.header }}</span>
                        </th>
                        <th v-if="rowAction || $slots.action" scope="col" class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-line">
                    <tr v-if="loading && !rows.length">
                        <td :colspan="activeColumns.length + (rowAction || $slots.action ? 1 : 0) + (selectable ? 1 : 0)" class="px-4 py-10 text-center text-ink-muted">Memuatkan…</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td :colspan="activeColumns.length + (rowAction || $slots.action ? 1 : 0) + (selectable ? 1 : 0)" class="px-4 py-12 text-center">
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
                        <td v-if="selectable" class="px-4 py-3" @click.stop>
                            <input
                                v-if="row.original.id"
                                type="checkbox"
                                class="size-4 accent-brand-600"
                                :checked="picked.includes(row.original.id)"
                                :aria-label="`Pilih ${row.original[activeColumns[0].key]}`.replace(/<[^>]*>/g, '')"
                                @change="togglePick(row.original.id)"
                            >
                        </td>
                        <td
                            v-for="cell in row.getVisibleCells()"
                            :key="cell.id"
                            :class="['px-4 py-3 align-middle', cell.column.columnDef.meta.align === 'right' ? 'text-right' : '']"
                        >
                            <slot :name="`cell-${cell.column.columnDef.meta.key}`" :row="row.original">
                                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />
                            </slot>
                        </td>

                        <td v-if="rowAction || $slots.action" class="px-4 py-3 text-right whitespace-nowrap" @click.stop>
                            <slot name="action" :row="row.original" />
                            <!-- A row action changes something for somebody else —
                                 approving a vendor emails them and puts them on the
                                 marketplace — so it asks first, like every other
                                 action in the application. -->
                            <UiConfirm
                                v-for="(rowItem, at) in (rowAction?.inline ? actionsFor(row.original) : [])"
                                :key="at"
                                :action="rowItem.url"
                                :method="rowItem.method || 'POST'"
                                :fields="rowItem.fields || {}"
                                :tone="rowItem.confirm?.tone || 'brand'"
                                :title="rowItem.confirm?.title || `${rowItem.label}?`"
                                :message="rowItem.confirm?.message"
                                :confirm-label="rowItem.confirm?.confirmLabel || rowItem.label"
                                :trigger-class="[
                                    'ml-2 rounded-full px-3 py-1.5 text-xs font-semibold transition',
                                    rowItem.tone === 'brand'
                                        ? 'bg-brand-600 text-white hover:bg-brand-700'
                                        : 'border border-line font-medium hover:border-brand-400',
                                ].join(' ')"
                                :csrf="csrf"
                            >{{ rowItem.label }}</UiConfirm>

                            <UiConfirm
                                v-if="rowAction && !rowAction.inline && row.original[rowAction.urlKey]"
                                :action="row.original[rowAction.urlKey]"
                                :method="rowAction.method || 'POST'"
                                :tone="rowAction.tone || 'brand'"
                                :title="rowAction.title.replace('__ROW__', row.original[rowAction.labelKey] || '')"
                                :message="rowAction.message"
                                :confirm-label="rowAction.confirmLabel"
                                trigger-class="rounded-full border border-line px-3 py-1.5 text-xs font-medium whitespace-nowrap transition hover:border-brand-400 hover:text-brand-700"
                                :csrf="csrf"
                            >{{ rowAction.label }}</UiConfirm>
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
