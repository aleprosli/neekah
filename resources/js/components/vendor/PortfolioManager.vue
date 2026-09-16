<script setup>
/**
 * The vendor's own portfolio: which photos the public sees, and in what order.
 *
 * Order is the whole story — the first five in this list fill the grid at the
 * top of the public page, and every visible photo is in the lightbox behind it.
 * Changes save as they are made, because a vendor dragging photos around does
 * not expect to hunt for a save button.
 */
import { computed, ref } from 'vue';

const props = defineProps({
    items: { type: Array, required: true },
    reorderUrl: { type: String, required: true },
    destroyUrlTemplate: { type: String, required: true },
    csrf: { type: String, required: true },
});

const HERO_COUNT = 5;

const photos = ref([...props.items]);
const dragging = ref(null);
const saving = ref(false);
const error = ref('');

const visibleCount = computed(() => photos.value.filter((photo) => photo.is_visible).length);

/** The public grid draws from visible photos only, so the badge must too. */
const heroIds = computed(() =>
    photos.value
        .filter((photo) => photo.is_visible)
        .slice(0, HERO_COUNT)
        .map((photo) => photo.id),
);

const save = async () => {
    saving.value = true;
    error.value = '';

    try {
        const response = await fetch(props.reorderUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf, Accept: 'application/json' },
            body: JSON.stringify({
                items: photos.value.map((photo, at) => ({ id: photo.id, sort_order: at, is_visible: photo.is_visible })),
            }),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
    } catch (problem) {
        error.value = 'Susunan tidak dapat disimpan. Muat semula halaman dan cuba lagi.';
        console.error(problem);
    } finally {
        saving.value = false;
    }
};

const toggle = (photo) => {
    photo.is_visible = !photo.is_visible;
    save();
};

const move = (from, to) => {
    if (to < 0 || to >= photos.value.length || from === to) return;

    photos.value.splice(to, 0, ...photos.value.splice(from, 1));
    save();
};

const onDrop = (to) => {
    if (dragging.value !== null) move(dragging.value, to);
    dragging.value = null;
};

const destroyUrl = (photo) => props.destroyUrlTemplate.replace('__ID__', photo.id);

/** Deleting removes the file for good, so it is the one action that asks. */
const confirmDelete = (event) => {
    if (!window.confirm('Padam gambar ini secara kekal?')) {
        event.preventDefault();
    }
};
</script>

<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">Susunan galeri</h2>
                <p class="text-sm text-ink-muted">
                    Seret untuk susun. <strong>{{ HERO_COUNT }} gambar pertama</strong> mengisi grid di atas halaman awam anda; selebihnya muncul apabila pengantin menekan "Tunjuk semua gambar".
                </p>
            </div>
            <p class="text-xs text-ink-muted" aria-live="polite">
                <span v-if="saving">Menyimpan…</span>
                <span v-else>{{ visibleCount }} daripada {{ photos.length }} dipaparkan</span>
            </p>
        </div>

        <p v-if="error" class="rounded-xl bg-brand-50 px-4 py-3 text-sm text-brand-800">{{ error }}</p>

        <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            <li
                v-for="(photo, at) in photos"
                :key="photo.id"
                draggable="true"
                :class="[
                    'group relative overflow-hidden rounded-2xl border transition',
                    dragging === at ? 'opacity-40' : '',
                    photo.is_visible ? 'border-line' : 'border-dashed border-line',
                ]"
                @dragstart="dragging = at"
                @dragover.prevent
                @drop="onDrop(at)"
                @dragend="dragging = null"
            >
                <img
                    :src="photo.thumbnail || photo.url"
                    :alt="photo.caption || ''"
                    loading="lazy"
                    :class="['aspect-square w-full cursor-grab object-cover transition', photo.is_visible ? '' : 'opacity-40 grayscale']"
                >

                <span v-if="heroIds.includes(photo.id)" class="absolute top-2 left-2 rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-semibold text-white">
                    Grid utama
                </span>
                <span v-else-if="!photo.is_visible" class="absolute top-2 left-2 rounded-full bg-ink/70 px-2 py-0.5 text-[11px] font-semibold text-white">
                    Disembunyikan
                </span>

                <p v-if="photo.caption" class="truncate px-3 py-2 text-xs text-ink-muted">{{ photo.caption }}</p>

                <div class="flex items-center gap-1 border-t border-line px-2 py-2">
                    <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === 0" aria-label="Alih ke kiri" @click="move(at, at - 1)">←</button>
                    <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === photos.length - 1" aria-label="Alih ke kanan" @click="move(at, at + 1)">→</button>

                    <button
                        type="button"
                        class="ml-auto rounded-full px-2.5 py-1 text-xs font-medium transition hover:bg-surface-muted"
                        @click="toggle(photo)"
                    >
                        {{ photo.is_visible ? 'Sembunyikan' : 'Paparkan' }}
                    </button>

                    <form :action="destroyUrl(photo)" method="POST" @submit="confirmDelete">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted hover:text-brand-700" aria-label="Padam">✕</button>
                    </form>
                </div>
            </li>
        </ul>
    </div>
</template>
