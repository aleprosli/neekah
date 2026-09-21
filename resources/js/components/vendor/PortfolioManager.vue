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
import UiConfirm from '../ui/UiConfirm.vue';
import { useUploadForm } from '../../composables/useUploadForm.js';
import UiField from '../ui/UiField.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';

const props = defineProps({
    items: { type: Array, required: true },
    reorderUrl: { type: String, required: true },
    storeUrl: { type: String, required: true },
    destroyUrlTemplate: { type: String, required: true },
    csrf: { type: String, required: true },
    imageHint: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const HERO_COUNT = 5;

const { uploading, percent: uploadPercent, error: uploadError, submit: submitUpload } = useUploadForm();
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
</script>

<template>
    <div class="flex flex-col gap-8">
        <form
            :action="storeUrl"
            method="POST"
            enctype="multipart/form-data"
            class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6"
            @submit="submitUpload"
        >
            <input type="hidden" name="_token" :value="csrf">

            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('portfolio.muat_naik_gambar') }}</span>
                <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700">
                <span class="text-xs text-ink-muted">{{ imageHint }}</span>
                <span class="text-xs text-ink-muted">{{ $t('portfolio.sehingga_10_gambar_sekali_gus') }}</span>
                <span v-if="errors.images" class="text-xs text-brand-700">{{ errors.images }}</span>
            </label>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <UiField name="caption" :label="$t('portfolio.kapsyen_pilihan')" :placeholder="$t('portfolio.majlis_aina_hakim_alor_setar')" class="min-w-0 flex-1" :error="errors.caption" />

                <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                    {{ uploading ? 'Memuat naik…' : 'Muat naik' }}
                </button>
            </div>

            <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" />
        </form>

        <p v-if="!photos.length" class="rounded-2xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">{{ $t('portfolio.belum_ada_gambar_portfolio_yang') }}</p>

        <div v-else class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">{{ $t('portfolio.susunan_galeri') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('portfolio.seret_untuk_susun') }}<strong>{{ HERO_COUNT }} gambar pertama</strong> mengisi grid di atas halaman awam anda; selebihnya muncul apabila pengantin menekan "Tunjuk semua gambar".
                </p>
            </div>
            <p class="text-xs text-ink-muted" aria-live="polite">
                <span v-if="saving">{{ $t('portfolio.menyimpan') }}</span>
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

                <span v-if="heroIds.includes(photo.id)" class="absolute top-2 left-2 rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $t('portfolio.grid_utama') }}</span>
                <span v-else-if="!photo.is_visible" class="absolute top-2 left-2 rounded-full bg-ink/70 px-2 py-0.5 text-[11px] font-semibold text-white">{{ $t('portfolio.disembunyikan') }}</span>

                <p v-if="photo.caption" class="truncate px-3 py-2 text-xs text-ink-muted">{{ photo.caption }}</p>

                <div class="flex items-center gap-1 border-t border-line px-2 py-2">
                    <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === 0" :aria-label="$t('portfolio.alih_ke_kiri')" @click="move(at, at - 1)">←</button>
                    <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === photos.length - 1" :aria-label="$t('portfolio.alih_ke_kanan')" @click="move(at, at + 1)">→</button>

                    <button
                        type="button"
                        class="ml-auto rounded-full px-2.5 py-1 text-xs font-medium transition hover:bg-surface-muted"
                        @click="toggle(photo)"
                    >
                        {{ photo.is_visible ? 'Sembunyikan' : 'Paparkan' }}
                    </button>

                    <UiConfirm
                        :action="destroyUrl(photo)"
                        method="DELETE"
                        :title="$t('portfolio.padam_gambar_ini')"
                        :message="$t('portfolio.fail_dibuang_untuk_selamanya_termasuk')"
                        confirm-:label="$t('portfolio.ya_padam')"
                        tone="danger"
                        trigger-class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted hover:text-brand-700"
                        :csrf="csrf"
                    >✕</UiConfirm>
                </div>
            </li>
        </ul>
        </div>
    </div>
</template>
