<script setup>
/**
 * What a new vendor has to do before their profile is worth showing, and — the
 * part a plain checklist never answers — what each photo is actually for and
 * where it lands on the public page.
 */
import { computed, ref } from 'vue';

const props = defineProps({
    steps: { type: Array, required: true },
    publicUrl: { type: String, default: null },
    dismissible: { type: Boolean, default: true },
});

const STORAGE_KEY = 'neekah:onboarding-collapsed';

const readCollapsed = () => {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
};

const collapsed = ref(readCollapsed());
const selected = ref(props.steps.findIndex((step) => !step.done));

if (selected.value === -1) selected.value = 0;

const done = computed(() => props.steps.filter((step) => step.done).length);
const percent = computed(() => Math.round((done.value / props.steps.length) * 100));
const allDone = computed(() => done.value === props.steps.length);
const step = computed(() => props.steps[selected.value]);

const toggle = () => {
    collapsed.value = !collapsed.value;
    try {
        window.localStorage.setItem(STORAGE_KEY, collapsed.value ? '1' : '0');
    } catch {
        // A private window simply gets the panel open every time.
    }
};
</script>

<template>
    <section class="overflow-hidden rounded-2xl border border-line bg-surface-raised">
        <header class="flex flex-wrap items-center gap-4 p-5">
            <div class="min-w-0 flex-1">
                <h2 class="font-display text-lg font-semibold">
                    {{ allDone ? 'Profil anda sudah lengkap' : 'Langkah wajib sebelum profil anda dipaparkan' }}
                </h2>
                <p class="mt-0.5 text-sm text-ink-muted">
                    {{ allDone
                        ? 'Semua yang pengantin perlu lihat sudah ada. Kemas kini bila-bila masa.'
                        : `${done} daripada ${steps.length} selesai. Profil yang lengkap disemak lebih cepat dan muncul lebih tinggi dalam carian.` }}
                </p>

                <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-surface-muted" role="progressbar" :aria-valuenow="percent" aria-valuemin="0" aria-valuemax="100">
                    <div class="h-full rounded-full bg-brand-600 transition-all duration-500" :style="{ width: `${percent}%` }"></div>
                </div>
            </div>

            <button v-if="dismissible" type="button" class="shrink-0 rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="toggle">
                {{ collapsed ? 'Buka panduan' : 'Sembunyikan' }}
            </button>
        </header>

        <div v-if="!collapsed" class="grid gap-0 border-t border-line lg:grid-cols-[minmax(0,280px)_1fr]">
            <ol class="flex flex-col border-line lg:border-r">
                <li v-for="(item, at) in steps" :key="item.key">
                    <button
                        type="button"
                        :class="[
                            'flex w-full items-center gap-3 px-5 py-3 text-left text-sm transition',
                            at === selected ? 'bg-surface-muted font-medium' : 'hover:bg-surface-muted',
                        ]"
                        @click="selected = at"
                    >
                        <span
                            :class="[
                                'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                item.done ? 'bg-emerald-500 text-white' : 'border border-line text-ink-muted',
                            ]"
                        >{{ item.done ? '✓' : at + 1 }}</span>
                        <span :class="['min-w-0 flex-1 truncate', item.done ? 'text-ink-muted' : '']">{{ item.label }}</span>
                    </button>
                </li>
            </ol>

            <div class="flex flex-col gap-4 p-5">
                <div>
                    <h3 class="font-semibold">{{ step.label }}</h3>
                    <p class="mt-1 text-sm text-ink-muted">{{ step.why }}</p>
                </div>

                <div v-if="step.specs?.length" class="flex flex-wrap gap-2">
                    <span v-for="spec in step.specs" :key="spec" class="rounded-full bg-surface-muted px-3 py-1 text-xs text-ink-muted">{{ spec }}</span>
                </div>

                <!-- Where it lands on the public page, drawn rather than described. -->
                <figure v-if="step.preview" class="rounded-xl border border-line bg-surface p-4">
                    <figcaption class="mb-3 text-xs font-medium text-ink-muted">Di halaman awam anda</figcaption>

                    <div class="flex flex-col gap-2">
                        <div class="grid h-24 grid-cols-4 grid-rows-2 gap-1 overflow-hidden rounded-lg">
                            <div :class="['col-span-2 row-span-2 rounded', step.preview === 'portfolio' ? 'bg-brand-600' : 'bg-surface-muted']"></div>
                            <div v-for="tile in 4" :key="tile" :class="['rounded', step.preview === 'portfolio' ? 'bg-brand-300' : 'bg-surface-muted']"></div>
                        </div>

                        <div class="flex gap-2">
                            <div class="flex flex-1 flex-col gap-1.5">
                                <div :class="['h-2.5 w-2/3 rounded', step.preview === 'profil' ? 'bg-brand-600' : 'bg-surface-muted']"></div>
                                <div :class="['h-2 w-full rounded', step.preview === 'profil' ? 'bg-brand-300' : 'bg-surface-muted']"></div>
                                <div class="mt-2 grid grid-cols-2 gap-1.5">
                                    <div v-for="card in 2" :key="card" class="flex flex-col gap-1 rounded border border-line p-1.5">
                                        <div :class="['h-6 rounded', step.preview === 'pakej' ? 'bg-brand-600' : 'bg-surface-muted']"></div>
                                        <div :class="['h-1.5 w-2/3 rounded', step.preview === 'pakej' ? 'bg-brand-300' : 'bg-surface-muted']"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex w-24 flex-col gap-1.5 rounded border border-line p-2">
                                <div :class="['h-2 w-3/4 rounded', step.preview === 'harga' ? 'bg-brand-600' : 'bg-surface-muted']"></div>
                                <div class="h-1.5 rounded bg-surface-muted"></div>
                                <div class="h-1.5 rounded bg-surface-muted"></div>
                                <div :class="['mt-1 h-3 rounded-full', step.preview === 'harga' ? 'bg-brand-600' : 'bg-surface-muted']"></div>
                            </div>
                        </div>
                    </div>
                </figure>

                <div class="mt-auto flex flex-wrap gap-2">
                    <a :href="step.href" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                        {{ step.done ? 'Kemas kini' : step.action }}
                    </a>
                    <a v-if="publicUrl" :href="publicUrl" target="_blank" rel="noopener" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">
                        Lihat halaman awam
                    </a>
                </div>
            </div>
        </div>
    </section>
</template>
