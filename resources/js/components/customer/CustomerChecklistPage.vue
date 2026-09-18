<script setup>
/**
 * The couple's checklist, walked one phase at a time: perancangan, borang
 * nikah, kursus, wali, persediaan, akad, majlis, selepas nikah. Each phase
 * carries its own progress, because "68% siap" across a hundred tasks tells
 * nobody whether the paperwork is done.
 *
 * Ticking posts, because two people plan one wedding and the other partner has
 * to see the same list on their own phone.
 */
import { ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    stats: { type: Array, required: true },
    progress: { type: Object, required: true },
    sections: { type: Array, required: true },
    categories: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

/** Everything unfinished starts open; a phase already done stays folded away. */
const open = ref(new Set(props.sections.filter((section) => section.done < section.total).map((section) => section.title)));

const toggle = (title) => {
    const next = new Set(open.value);
    next.has(title) ? next.delete(title) : next.add(title);
    open.value = next;
};

const tone = (section) => {
    if (section.done === section.total) return 'bg-emerald-500';
    if (section.overdue > 0) return 'bg-red-500';

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
            <span class="text-ink-muted">{{ progress.caption }}</span>
        </div>
        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-muted">
            <div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: `${progress.percent}%` }"></div>
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

    <section class="mt-8 flex flex-col gap-3">
        <div
            v-for="(section, at) in sections"
            :key="section.title"
            class="min-w-0 overflow-hidden rounded-2xl border border-line bg-surface-raised"
        >
            <button type="button" class="flex w-full items-center gap-3 p-5 text-left" @click="toggle(section.title)">
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
                        <li v-for="task in group.tasks" :key="task.id" class="flex items-center gap-3 p-4">
                            <form :action="task.update_url" method="POST">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="PUT">
                                <input type="hidden" name="done" :value="task.done ? '0' : '1'">
                                <button
                                    v-if="task.done"
                                    type="submit"
                                    class="flex size-6 items-center justify-center rounded-full bg-emerald-500 text-xs text-white"
                                    aria-label="Buka semula"
                                >✓</button>
                                <button
                                    v-else
                                    type="submit"
                                    class="flex size-6 items-center justify-center rounded-full border-2 border-line transition hover:border-brand-500 hover:bg-brand-50"
                                    aria-label="Tandakan selesai"
                                ></button>
                            </form>

                            <div class="min-w-0 flex-1">
                                <p class="truncate font-medium" :class="task.done ? 'text-ink-muted line-through' : ''">{{ task.title }}</p>
                                <p class="flex flex-wrap items-center gap-x-2 text-xs text-ink-muted">
                                    <span v-if="task.done">{{ task.completed }}</span>
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

                            <a v-if="task.category && !task.done" :href="task.category.vendors_url" class="hidden shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400 sm:inline">Cari vendor</a>

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
</template>
