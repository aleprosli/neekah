<script setup>
/**
 * The couple's checklist. Ticking a task posts, because two people plan one
 * wedding and the other partner has to see the same list on their own phone.
 */
import UiConfirm from '../ui/UiConfirm.vue';
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    stats: { type: Array, required: true },
    progress: { type: Object, required: true },
    todo: { type: Array, required: true },
    done: { type: Array, required: true },
    categories: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
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

    <section class="mt-8 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">Belum selesai ({{ todo.length }})</h2>

        <p v-if="!todo.length" class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Semua tugasan selesai. Tahniah!</p>

        <ul v-else class="divide-y divide-line rounded-2xl border border-line">
            <li v-for="task in todo" :key="task.id" class="flex items-center gap-3 p-4">
                <form :action="task.update_url" method="POST">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="done" value="1">
                    <button type="submit" class="flex size-6 items-center justify-center rounded-full border-2 border-line transition hover:border-brand-500 hover:bg-brand-50" aria-label="Tandakan selesai"></button>
                </form>

                <div class="min-w-0 flex-1">
                    <p class="truncate font-medium">{{ task.title }}</p>
                    <p class="flex flex-wrap items-center gap-x-2 text-xs text-ink-muted">
                        <span v-if="task.category" class="flex items-center gap-1">
                            <img v-if="task.category.illustration" :src="task.category.illustration" alt="" class="size-5 object-contain mix-blend-multiply">
                            <span v-else aria-hidden="true">{{ task.category.icon }}</span>
                            {{ task.category.name }}
                        </span>
                        <span v-if="task.due" :class="task.overdue ? 'font-medium text-red-600' : ''">{{ task.due }}</span>
                    </p>
                </div>

                <a v-if="task.category" :href="task.category.vendors_url" class="hidden shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400 sm:inline">Cari vendor</a>

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
    </section>

    <details v-if="done.length" class="mt-8">
        <summary class="cursor-pointer font-display text-xl font-semibold [&::-webkit-details-marker]:hidden">Selesai ({{ done.length }})</summary>

        <ul class="mt-4 divide-y divide-line rounded-2xl border border-line">
            <li v-for="task in done" :key="task.id" class="flex items-center gap-3 p-4">
                <form :action="task.update_url" method="POST">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="done" value="0">
                    <button type="submit" class="flex size-6 items-center justify-center rounded-full bg-emerald-500 text-xs text-white" aria-label="Buka semula">✓</button>
                </form>

                <div class="min-w-0 flex-1">
                    <p class="truncate text-ink-muted line-through">{{ task.title }}</p>
                    <p class="text-xs text-ink-muted">{{ task.completed }}</p>
                </div>
            </li>
        </ul>
    </details>
</template>
