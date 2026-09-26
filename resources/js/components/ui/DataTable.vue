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
import UiColumnToggle from './UiColumnToggle.vue';
import UiConfirm from './UiConfirm.vue';
import UiFacetedFilter from './UiFacetedFilter.vue';

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
     * Filters shown above the table, each a button opening a checklist
     * (UiFacetedFilter), each group
     * { key, label?, value, exclusive?, multiple?, hint?, options: [{ value, label, count?, description? }] }.
     * `value` is what is chosen now: an array, or a comma-separated string.
     * Several values of one group widen it (paid OR pending); groups narrow
     * each other (Pro AND paid) unless a group is marked exclusive, which
     * clears the others as the users page needs. The server is sent
     * key=a,b and reads it with App\Support\TableFilter::requested().
     */
    filters: { type: Array, default: () => [] },
    searchPlaceholder: { type: String, default: 'Cari…' },
    emptyTitle: { type: String, default: null },
    emptyMessage: { type: String, default: null },
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
    /** Endpoint that returns the list as a file; it is handed the same filters and search. */
    exportUrl: { type: String, default: null },
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

/** What a group's value arrived as — an array, "a,b", or nothing — as an array. */
const asList = (value) => (Array.isArray(value) ? value : String(value ?? '').split(',')).map(String).filter(Boolean);

const selected = ref(Object.fromEntries(props.filters.map((group) => [group.key, asList(group.value)])));
const liveCounts = ref(null);

/** The freshest count the endpoint gave for one value, else the one it was born with. */
const countFor = (group, value) => {
    const live = liveCounts.value?.[group.key];

    if (live && Object.prototype.hasOwnProperty.call(live, value)) {
        return live[value];
    }

    return group.options.find((option) => option.value === value)?.count ?? null;
};

/** A group's options with their live counts, for its checklist. */
const optionsOf = (group) => group.options.map((option) => ({ ...option, count: countFor(group, option.value) }));

/**
 * Groups narrow each other, so a status and a setup state can be asked for at
 * once. An exclusive group (the users page's role and segment, which the
 * server reads one of) clears the rest instead.
 */
const chooseFilter = (group, values) => {
    if (group.exclusive && values.length) {
        Object.keys(selected.value).forEach((key) => {
            if (key !== group.key) selected.value[key] = [];
        });
    }

    selected.value[group.key] = values;
    page.value = 1;
    load();
    rememberFilters();
};

/** Every value chosen, as a removable chip under the toolbar. */
const activeChips = computed(() =>
    props.filters.flatMap((group) =>
        (selected.value[group.key] ?? []).map((value) => ({
            group,
            value,
            label: group.options.find((option) => option.value === value)?.label ?? value,
        })),
    ),
);

const removeChip = (chip) => chooseFilter(chip.group, selected.value[chip.group.key].filter((one) => one !== chip.value));

const resetFilters = () => {
    Object.keys(selected.value).forEach((key) => (selected.value[key] = []));
    search.value = '';
    page.value = 1;
    load();
    rememberFilters();
};

/** The filters as query parameters: key=a,b for each group with a choice. */
const filterQuery = () => Object.fromEntries(Object.entries(selected.value).filter(([, values]) => values.length).map(([key, values]) => [key, values.join(',')]));

/** Keep the address bar in step, so a refresh or a shared link filters too. */
const rememberFilters = () => {
    const url = new URL(window.location.href);
    const query = filterQuery();
    props.filters.forEach((group) => (query[group.key] ? url.searchParams.set(group.key, query[group.key]) : url.searchParams.delete(group.key)));
    window.history.replaceState(window.history.state, '', url);
};

/**
 * Which columns show, remembered per list in this browser. Storage can be
 * missing or refuse (a private window); the table then shows everything.
 */
const visibilityKey = computed(() => `nk-table-columns:${props.dataUrl ? new URL(props.dataUrl, window.location.origin).pathname : window.location.pathname}`);
const readVisibility = () => {
    try {
        return JSON.parse(localStorage.getItem(visibilityKey.value) ?? '{}') ?? {};
    } catch {
        return {};
    }
};
const hidden = ref(readVisibility());
const toggleColumn = (key) => {
    hidden.value = { ...hidden.value, [key]: !hidden.value[key] };
    try {
        localStorage.setItem(visibilityKey.value, JSON.stringify(hidden.value));
    } catch {
        // Not remembered, still applied.
    }
};
const visibleColumns = computed(() => activeColumns.value.filter((column) => !hidden.value[column.key]));
const columnChoices = computed(() => activeColumns.value.map((column) => ({ key: column.key, label: column.label, visible: !hidden.value[column.key] })));

/** Rows per page, chosen from a short list; the first is the page's own default. */
const pageSize = ref(props.perPage);
const pageSizes = computed(() => [...new Set([props.perPage, 25, 50, 100])].sort((a, b) => a - b));
const setPageSize = (size) => {
    pageSize.value = Number(size);
    page.value = 1;
    load();
};

const columnDefs = computed(() =>
    visibleColumns.value.map((column) => ({
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
        per_page: pageSize.value,
        ...filterQuery(),
        ...(search.value ? { search: search.value } : {}),
        ...(sort.value ? { sort: sort.value, direction: direction.value } : {}),
    };
    Object.entries(query).forEach(([key, value]) => url.searchParams.set(key, value));
    props.filters.forEach((group) => {
        if (!query[group.key]) {
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
        // Counts that follow the filters: the endpoint counts each group
        // against what the others left, so a chip cannot claim 60 rows above
        // an empty table.
        liveCounts.value = payload.filters ?? null;
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
    const last = visibleColumns.value.at(-1);

    return visibleColumns.value.filter(
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

/** The same list the table is showing, as a download. */
const exportHref = computed(() => {
    if (!props.exportUrl) {
        return null;
    }

    const url = new URL(props.exportUrl, window.location.origin);
    Object.entries(filterQuery()).forEach(([key, value]) => url.searchParams.set(key, value));
    if (search.value) {
        url.searchParams.set('search', search.value);
    }

    return url.toString();
});

/** "16–30 daripada 412", for the footer. */
const range = computed(() => {
    if (!meta.value.total) return null;
    const from = (meta.value.current_page - 1) * (meta.value.per_page ?? pageSize.value) + 1;

    return { from, to: Math.min(meta.value.total, from + rows.value.length - 1), total: meta.value.total };
});

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
    <!-- The whole table is one white card — toolbar, chips, rows, pager —
         so it reads as one thing against the page's ivory. -->
    <div class="flex min-w-0 flex-col overflow-clip rounded-3xl border border-line bg-surface-raised shadow-sm">
        <!-- One toolbar: search, one button per filter, columns, export. Ten
             filters stay on one line (wrapping on a phone); each opens a
             checklist, so a filter can take several values. -->
        <div v-if="!isStatic || filters.length || $slots.actions" class="flex min-w-0 flex-col gap-3 border-b border-line p-4 sm:p-5">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <label v-if="!isStatic" class="relative w-full min-w-0 sm:w-64">
                    <span class="sr-only">{{ searchPlaceholder }}</span>
                    <svg class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="m20 20-3.5-3.5" /></svg>
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="searchPlaceholder"
                        class="h-9 w-full rounded-full border border-line bg-surface pr-4 pl-9 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none"
                    >
                </label>

                <UiFacetedFilter
                    v-for="group in filters"
                    :key="group.key"
                    :label="group.label || group.key"
                    :options="optionsOf(group)"
                    :model-value="selected[group.key]"
                    :multiple="group.multiple !== false"
                    :hint="group.hint"
                    @update:model-value="chooseFilter(group, $event)"
                />

                <button v-if="activeChips.length || search" type="button" class="h-9 shrink-0 rounded-full px-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink" @click="resetFilters">{{ $t('common.filter_reset') }} ✕</button>

                <div class="ml-auto flex shrink-0 items-center gap-2">
                    <slot name="actions" />
                    <!-- The card layout has no headers to click, so it sorts from a select. -->
                    <label v-if="sortableColumns.length && !isStatic" class="min-w-0 md:hidden">
                        <span class="sr-only">{{ $t('common.sort_by') }}</span>
                        <select
                            class="nk-select h-9 w-full rounded-full border border-line bg-surface px-3 pr-9 text-sm focus:border-brand-400 focus:outline-none"
                            :value="`${sort}:${direction}`"
                            @change="sortFromSelect($event.target.value)"
                        >
                            <option v-if="!sort" :value="`${sort}:${direction}`" disabled>{{ $t('common.sort_by') }}</option>
                            <option v-for="column in sortableColumns" :key="column.key" :value="`${sortKey(column)}:desc`">{{ column.label }} ↓</option>
                            <option v-for="column in sortableColumns" :key="`${column.key}-asc`" :value="`${sortKey(column)}:asc`">{{ column.label }} ↑</option>
                        </select>
                    </label>
                    <span v-if="activeColumns.length > 3" class="hidden md:inline-flex"><UiColumnToggle :columns="columnChoices" @toggle="toggleColumn" /></span>
                    <!-- Downloads exactly what is on screen: same filters, same search. -->
                    <a
                        v-if="exportHref"
                        :href="exportHref"
                        class="inline-flex h-9 items-center gap-2 rounded-full border border-line px-3.5 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700"
                    >
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12M7 11l5 5 5-5M5 21h14"/></svg>
                        CSV
                    </a>
                </div>
            </div>

            <div v-if="activeChips.length || !isStatic" class="flex min-w-0 flex-wrap items-center gap-2">
                <span v-for="chip in activeChips" :key="`${chip.group.key}:${chip.value}`" class="inline-flex max-w-full items-center gap-1.5 rounded-full bg-brand-50 py-1 pr-1 pl-3 text-xs font-medium text-brand-800">
                    <span class="truncate"><span class="text-brand-600/80">{{ chip.group.label || chip.group.key }}:</span> {{ chip.label }}</span>
                    <button type="button" class="flex size-5 shrink-0 items-center justify-center rounded-full hover:bg-brand-100" :aria-label="$t('common.filter_remove', { label: chip.label })" @click="removeChip(chip)">✕</button>
                </span>
                <p v-if="!isStatic" class="text-xs text-ink-muted" aria-live="polite">
                    <span v-if="loading">{{ $t('common.memuatkan') }}…</span>
                    <span v-else>{{ $t('common.records', { count: meta.total }) }}</span>
                </p>
            </div>
        </div>

        <!-- What the ticked rows can be done to, shown only once something is
             ticked so it never sits in the way. -->
        <div v-if="selectable && picked.length" class="sticky top-0 z-20 flex flex-wrap items-center gap-3 border-b border-brand-200 bg-brand-50 px-4 py-3 sm:px-5">
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
            <button type="button" class="ml-auto text-xs font-medium text-ink-muted underline underline-offset-4 hover:text-ink" @click="picked = []">{{ $t('common.clear_selection') }}</button>
        </div>

        <p v-if="failed" class="border-b border-line bg-brand-50 px-5 py-3 text-sm text-brand-800">
            Senarai tidak dapat dimuatkan. Muat semula halaman untuk cuba lagi.
        </p>

        <!-- Cards on a phone, a table from md up: a row with seven columns is
             unreadable on a 390px screen however far it scrolls. -->
        <!-- min-w-0 the whole way down: a flex child sizes to its content by
             default, so one long business name pushes the card past the screen. -->
        <ul v-if="rows.length" class="flex min-w-0 flex-col divide-y divide-line md:hidden">
            <li v-for="row in table.getRowModel().rows" :key="`card-${row.id}`" class="min-w-0 p-4">
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
                            <slot :name="`cell-${visibleColumns[0].key}`" :row="row.original">
                                <span v-if="visibleColumns[0].type === 'html'" v-html="row.original[visibleColumns[0].key]"></span>
                                <span v-else>{{ row.original[visibleColumns[0].key] }}</span>
                            </slot>
                        </p>
                        <span v-if="visibleColumns.at(-1).type === 'html'" class="shrink-0" v-html="row.original[visibleColumns.at(-1).key]"></span>
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

        <p v-else-if="loading" class="p-8 text-center text-sm text-ink-muted md:hidden">{{ $t('common.memuatkan') }}…</p>

        <div v-else class="p-8 text-center md:hidden">
            <p class="font-medium">{{ emptyTitle ?? $t('common.tiada_rekod') }}</p>
            <p class="mt-1 text-sm text-ink-muted">{{ emptyMessage ?? $t('common.tiada_untuk_dipaparkan') }}</p>
        </div>

        <div class="hidden min-w-0 overflow-x-auto md:block">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="border-b border-line bg-surface-muted/60">
                    <tr>
                        <th v-if="selectable" scope="col" class="w-10 px-4 py-3">
                            <input
                                type="checkbox"
                                class="size-4 accent-brand-600"
                                :checked="allPagePicked"
                                :disabled="!pageIds.length"
                                :aria-label="$t('common.select_all_page')"
                                @change="togglePage"
                            >
                        </th>
                        <th
                            v-for="header in table.getHeaderGroups()[0].headers"
                            :key="header.id"
                            scope="col"
                            :class="['px-4 py-3 text-xs font-semibold tracking-wide whitespace-nowrap text-ink-muted uppercase', header.column.columnDef.meta.align === 'right' ? 'text-right' : '']"
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
                        <th v-if="rowAction || $slots.action" scope="col" class="px-4 py-3"><span class="sr-only">{{ $t('common.actions') }}</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-line">
                    <tr v-if="loading && !rows.length">
                        <td :colspan="visibleColumns.length + (rowAction || $slots.action ? 1 : 0) + (selectable ? 1 : 0)" class="px-4 py-10 text-center text-ink-muted">{{ $t('common.memuatkan') }}…</td>
                    </tr>
                    <tr v-else-if="!rows.length">
                        <td :colspan="visibleColumns.length + (rowAction || $slots.action ? 1 : 0) + (selectable ? 1 : 0)" class="px-4 py-12 text-center">
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

        <div v-if="!isStatic && meta.total" class="flex flex-wrap items-center justify-between gap-3 border-t border-line px-4 py-3 sm:px-5">
            <div class="flex items-center gap-3 text-xs text-ink-muted">
                <span v-if="range">{{ $t('common.range_of', range) }}</span>
                <label class="flex items-center gap-2">
                    <span class="hidden sm:inline">{{ $t('common.per_page') }}</span>
                    <select class="nk-select h-8 rounded-full border border-line bg-surface px-3 pr-8 text-xs focus:border-brand-400 focus:outline-none" :value="pageSize" @change="setPageSize($event.target.value)">
                        <option v-for="size in pageSizes" :key="size" :value="size">{{ size }}</option>
                    </select>
                </label>
            </div>
            <div v-if="meta.last_page > 1" class="flex items-center gap-1">
                <button type="button" class="flex size-9 items-center justify-center rounded-full border border-line text-sm transition hover:border-brand-400 disabled:opacity-40" :disabled="meta.current_page <= 1" :aria-label="$t('common.first_page')" @click="go(1)">«</button>
                <button type="button" class="flex size-9 items-center justify-center rounded-full border border-line text-sm transition hover:border-brand-400 disabled:opacity-40" :disabled="meta.current_page <= 1" :aria-label="$t('common.previous')" @click="go(meta.current_page - 1)">‹</button>
                <span class="px-3 text-xs text-ink-muted tabular-nums">{{ $t('common.halaman_x_daripada_y', { current: meta.current_page, last: meta.last_page }) }}</span>
                <button type="button" class="flex size-9 items-center justify-center rounded-full border border-line text-sm transition hover:border-brand-400 disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" :aria-label="$t('common.next')" @click="go(meta.current_page + 1)">›</button>
                <button type="button" class="flex size-9 items-center justify-center rounded-full border border-line text-sm transition hover:border-brand-400 disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" :aria-label="$t('common.last_page')" @click="go(meta.last_page)">»</button>
            </div>
        </div>
    </div>
</template>
