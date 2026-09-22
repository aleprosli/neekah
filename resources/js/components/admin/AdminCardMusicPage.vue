<script setup>
/**
 * The background-music library couples choose from.
 *
 * Admin uploads every file, so nothing on a card is hotlinked and nothing arrives
 * from a guest. A track a card is already playing can be turned off but not deleted:
 * deleting it would silence that card with nobody told.
 */
import { ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiField from '../ui/UiField.vue';
import UiStatCard from '../ui/UiStatCard.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';
import { useUploadForm } from '../../composables/useUploadForm.js';

const props = defineProps({
    storeUrl: { type: String, required: true },
    stats: { type: Array, default: () => [] },
    tracks: { type: Array, default: () => [] },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const { uploading, percent, error, submit } = useUploadForm();

const draft = ref({ title: '', artist: '' });
const editing = ref(null);

const edit = (track) => {
    editing.value = editing.value?.id === track.id ? null : { ...track };
};
</script>

<template>
    <div class="flex flex-col gap-8">
        <div class="grid gap-3 sm:grid-cols-3">
            <UiStatCard v-for="stat in stats" :key="stat.label" :label="stat.label" :value="stat.value" />
        </div>

        <form :action="storeUrl" method="POST" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6" @submit="submit">
            <input type="hidden" name="_token" :value="csrf">
            <h2 class="font-semibold">{{ $t('card_music.tambah_trek') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="draft.title" :label="$t('card_music.tajuk')" name="title" :error="errors.title" required />
                <UiField v-model="draft.artist" :label="$t('card_music.artis_pilihan')" name="artist" :error="errors.artist" />
            </div>

            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('card_music.fail_audio') }}</span>
                <input type="file" name="audio" accept="audio/mpeg,audio/mp4,audio/aac,audio/ogg" required class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700">
                <span class="text-xs text-ink-muted">{{ $t('card_music.mp3_sehingga_8mb') }}</span>
                <span v-if="errors.audio" class="text-xs text-brand-700">{{ errors.audio }}</span>
            </label>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" checked class="accent-brand-600">{{ $t('card_music.tawarkan_kepada_pengantin') }}</label>

            <UiUploadProgress :uploading="uploading" :percent="percent" :error="error" />

            <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                {{ uploading ? $t('common.memuat_naik') : $t('card_music.tambah') }}
            </button>
        </form>

        <section class="flex flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">{{ $t('card_music.pustaka') }}</h2>
            <p v-if="!tracks.length" class="text-sm text-ink-muted">{{ $t('card_music.belum_ada_trek') }}</p>

            <ul class="flex flex-col gap-3">
                <li v-for="track in tracks" :key="track.id" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold">{{ track.title }}</p>
                            <p class="truncate text-xs text-ink-muted">
                                {{ track.artist || '—' }}<span v-if="track.length"> · {{ track.length }}</span> · {{ $t('card_music.digunakan_pada_kad', { count: track.cards }) }}
                            </p>
                        </div>
                        <span :class="['shrink-0 rounded-full px-3 py-1 text-xs font-medium', track.is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-surface-muted text-ink-muted']">
                            {{ track.is_active ? $t('card_music.aktif') : $t('card_music.tidak_aktif') }}
                        </span>
                        <button type="button" class="shrink-0 rounded-full border border-line px-4 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="edit(track)">
                            {{ editing?.id === track.id ? $t('card_music.tutup') : $t('card_music.sunting') }}
                        </button>
                        <UiConfirm
                            v-if="!track.cards"
                            :action="track.destroy_url"
                            method="DELETE"
                            tone="danger"
                            :title="$t('card_music.padam_trek', { title: track.title })"
                            :message="$t('card_music.fail_akan_dibuang_terus')"
                            :confirm-label="$t('card_music.padam')"
                            :csrf="csrf"
                            :label="$t('card_music.padam')"
                            trigger-class="shrink-0 rounded-full border border-line px-4 py-1.5 text-xs font-medium transition hover:border-red-300 hover:text-red-700"
                        />
                    </div>

                    <audio :src="track.url" controls preload="none" class="w-full max-w-sm" />

                    <form v-if="editing?.id === track.id" :action="track.update_url" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 border-t border-line pt-3" @submit="submit">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="_method" value="PUT">

                        <div class="grid gap-3 sm:grid-cols-2">
                            <UiField v-model="editing.title" :label="$t('card_music.tajuk')" name="title" required />
                            <UiField v-model="editing.artist" :label="$t('card_music.artis_pilihan')" name="artist" />
                        </div>

                        <label class="flex flex-col gap-1.5">
                            <span class="text-sm font-medium">{{ $t('card_music.ganti_fail_pilihan') }}</span>
                            <input type="file" name="audio" accept="audio/mpeg,audio/mp4,audio/aac,audio/ogg" class="text-sm">
                        </label>

                        <label class="flex items-center gap-2 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input v-model="editing.is_active" type="checkbox" name="is_active" value="1" class="accent-brand-600">{{ $t('card_music.tawarkan_kepada_pengantin') }}</label>

                        <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('card_music.simpan') }}</button>
                    </form>
                </li>
            </ul>
        </section>
    </div>
</template>
