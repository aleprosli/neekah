<script setup>
/**
 * The couple's checklist, walked one phase at a time: perancangan, borang
 * nikah, kursus, wali, persediaan, akad, majlis, selepas nikah. Each phase
 * carries its own progress, because "68% siap" across a hundred tasks tells
 * nobody whether the paperwork is done.
 *
 * Ticking is local until it is saved. Somebody sitting down with their folder
 * ticks eight documents in a row, and a form post per tick made that eight page
 * reloads, each one scrolling them back to the top and folding the phase they
 * were working in. The save bar sends the lot in one request — it is still a
 * round trip in the end, because two people plan one wedding and the other
 * partner has to see the same list on their own phone.
 */
import { computed, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    sections: { type: Array, required: true },
    categories: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    updateUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

/** Everything unfinished starts open; a phase already done stays folded away. */
const open = ref(new Set(props.sections.filter((section) => section.done < section.total).map((section) => section.title)));

const toggleSection = (title) => {
    const next = new Set(open.value);
    next.has(title) ? next.delete(title) : next.add(title);
    open.value = next;
};

/** id => ticked, for the tasks whose state differs from what the server holds. */
const pending = ref(new Map());

const tasks = computed(() => props.sections.flatMap((section) => section.groups.flatMap((group) => group.tasks)));

const isDone = (task) => (pending.value.has(task.id) ? pending.value.get(task.id) : task.done);

/** Ticking back to where it started is not a change, so it leaves the list. */
const toggleTask = (task) => {
    const next = new Map(pending.value);

    next.has(task.id) ? next.delete(task.id) : next.set(task.id, !task.done);
    pending.value = next;
};

const ticked = computed(() => [...pending.value].filter(([, done]) => done).map(([id]) => id));
const unticked = computed(() => [...pending.value].filter(([, done]) => !done).map(([id]) => id));
const unsaved = computed(() => pending.value.size);

const discard = () => {
    pending.value = new Map();
};

/** Counts move as the couple ticks, before anything has been saved. */
const phases = computed(() =>
    props.sections.map((section) => {
        const rows = section.groups.flatMap((group) => group.tasks);
        const done = rows.filter(isDone).length;

        return {
            ...section,
            done,
            total: rows.length,
            percent: rows.length ? Math.round((done / rows.length) * 100) : 0,
            overdue: rows.filter((task) => task.overdue && !isDone(task)).length,
        };
    }),
);

const overall = computed(() => {
    const done = tasks.value.filter(isDone).length;
    const overdue = tasks.value.filter((task) => task.overdue && !isDone(task)).length;

    return {
        done,
        overdue,
        total: tasks.value.length,
        percent: tasks.value.length ? Math.round((done / tasks.value.length) * 100) : 0,
    };
});

/** Counted here rather than on the server, so the cards move with the ticks. */
const stats = computed(() => [
    { label: 'Progress', value: `${overall.value.percent}%`, hint: `${overall.value.done} daripada ${overall.value.total} selesai` },
    { label: 'Belum selesai', value: overall.value.total - overall.value.done, hint: 'Termasuk tugasan akan datang' },
    { label: 'Lewat', value: overall.value.overdue, hint: overall.value.overdue ? 'Perlu perhatian segera' : 'Semua mengikut jadual' },
]);

const tone = (progress) => {
    if (progress.total > 0 && progress.done === progress.total) return 'bg-emerald-500';
    if (progress.overdue > 0) return 'bg-red-500';

    return 'bg-brand-600';
};
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-3">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>

    <div class="mt-6 rounded-2xl border border-line bg-surface-raised p-5">
        <div class="flex flex-wrap items-center justify-between gap-x-3 text-sm">
            <span class="font-medium">Kemajuan keseluruhan</span>
            <span class="text-ink-muted">{{ overall.done }} / {{ overall.total }}</span>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-muted">
            <div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: `${overall.percent}%` }"></div>
        </div>
    </div>

    <form :action="storeUrl" method="POST" class="mt-6 flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5 sm:flex-row sm:items-end">
        <input type="hidden" name="_token" :value="csrf">

        <label class="flex min-w-0 flex-1 flex-col gap-1.5">
            <span class="text-sm font-medium">Tambah tugasan</span>
            <input type="text" name="title" placeholder="Contoh: Tempah kereta pengantin" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            <span v-if="errors.title" class="text-xs text-brand-700">{{ errors.title }}</span>
        </label>

        <label class="flex flex-col gap-1.5 sm:w-44">
            <span class="text-sm font-medium">Kategori</span>
            <select name="category_id" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <option value="">Tiada</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.icon }} {{ category.name }}</option>
            </select>
        </label>

        <label class="flex flex-col gap-1.5 sm:w-40">
            <span class="text-sm font-medium">Tarikh akhir</span>
            <input type="date" name="due_date" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
        </label>

        <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah</button>
    </form>

    <!-- One form for the whole checklist: tick as many as you like, save once. -->
    <form :action="updateUrl" method="POST">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="PUT">
        <input v-for="id in ticked" :key="`done-${id}`" type="hidden" name="done[]" :value="id">
        <input v-for="id in unticked" :key="`undone-${id}`" type="hidden" name="undone[]" :value="id">

        <section class="mt-8 flex flex-col gap-3" :class="unsaved ? 'pb-24' : ''">
            <div
                v-for="(section, at) in phases"
                :key="section.title"
                class="min-w-0 overflow-hidden rounded-2xl border border-line bg-surface-raised"
            >
                <button type="button" class="flex w-full items-center gap-3 p-5 text-left" @click="toggleSection(section.title)">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-xl" aria-hidden="true">{{ section.icon || '☑' }}</span>

                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-xs text-ink-muted">Fasa {{ String(at + 1).padStart(2, '0') }}</span>
                        <span class="block truncate font-display text-lg font-semibold">{{ section.title }}</span>

                        <span class="mt-2 block h-2 overflow-hidden rounded-full bg-surface-muted">
                            <span class="block h-full rounded-full transition-all" :class="tone(section)" :style="{ width: `${section.percent}%` }"></span>
                        </span>
                    </span>

                    <span class="shrink-0 text-right">
                        <span class="block text-sm font-semibold">{{ section.done }}/{{ section.total }}</span>
                        <span v-if="section.overdue" class="block text-xs font-medium text-red-600">{{ section.overdue }} lewat</span>
                        <span v-else-if="section.done === section.total" class="block text-xs text-emerald-600">Selesai</span>
                    </span>
                </button>

                <div v-if="open.has(section.title)" class="border-t border-line">
                    <p v-if="section.note" class="bg-surface-muted p-4 text-xs text-ink-muted">{{ section.note }}</p>

                    <div v-for="group in section.groups" :key="group.heading">
                        <p v-if="group.heading" class="border-b border-line px-5 pt-4 pb-2 text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ group.heading }}</p>

                        <ul class="divide-y divide-line">
                            <li
                                v-for="task in group.tasks"
                                :key="task.id"
                                class="flex items-center gap-3 p-4 transition"
                                :class="pending.has(task.id) ? 'bg-brand-50' : ''"
                            >
                                <button
                                    type="button"
                                    :class="[
                                        'flex size-6 shrink-0 items-center justify-center rounded-full text-xs transition',
                                        isDone(task) ? 'bg-emerald-500 text-white' : 'border-2 border-line hover:border-brand-500 hover:bg-brand-50',
                                    ]"
                                    :aria-pressed="isDone(task)"
                                    :aria-label="isDone(task) ? 'Buka semula' : 'Tandakan selesai'"
                                    @click="toggleTask(task)"
                                >{{ isDone(task) ? '✓' : '' }}</button>

                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-medium" :class="isDone(task) ? 'text-ink-muted line-through' : ''">{{ task.title }}</p>
                                    <p class="flex flex-wrap items-center gap-x-2 text-xs text-ink-muted">
                                        <span v-if="isDone(task) && task.completed && !pending.has(task.id)">{{ task.completed }}</span>
                                        <span v-else-if="pending.has(task.id)" class="font-medium text-brand-700">Belum disimpan</span>
                                        <template v-else>
                                            <span v-if="task.category" class="flex items-center gap-1">
                                                <img v-if="task.category.illustration" :src="task.category.illustration" alt="" class="size-5 object-contain mix-blend-multiply">
                                                <span v-else aria-hidden="true">{{ task.category.icon }}</span>
                                                {{ task.category.name }}
                                            </span>
                                            <span v-if="task.due" :class="task.overdue ? 'font-medium text-red-600' : ''">{{ task.due }}</span>
                                        </template>
                                    </p>
                                    <p v-if="task.notes" class="mt-1 text-xs text-ink-muted">{{ task.notes }}</p>
                                </div>

                                <a v-if="task.category && !isDone(task)" :href="task.category.vendors_url" class="hidden shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400 sm:inline">Cari vendor</a>

                                <UiConfirm
                                    :action="task.destroy_url"
                                    method="DELETE"
                                    tone="danger"
                                    title="Padam tugasan ini?"
                                    :message="task.title"
                                    confirm-label="Padam"
                                    trigger-class="shrink-0 text-xs font-medium text-ink-muted hover:text-brand-700"
                                    :csrf="csrf"
                                >Padam</UiConfirm>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <p v-if="!sections.length" class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">
                Checklist anda masih kosong. Tambah tugasan pertama di atas.
            </p>
        </section>

        <!-- lg:left-64 keeps the bar beside the dashboard sidebar, not over it. -->
        <div v-if="unsaved" class="fixed inset-x-0 bottom-0 z-30 border-t border-line bg-surface-raised p-4 shadow-2xl lg:left-64">
            <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-3">
                <p class="text-sm">
                    <span class="font-semibold">{{ unsaved }} perubahan</span>
                    <span class="text-ink-muted"> belum disimpan</span>
                </p>

                <div class="flex items-center gap-2">
                    <button type="button" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:bg-surface-muted" @click="discard">Batal</button>
                    <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan</button>
                </div>
            </div>
        </div>
    </form>
</template>
