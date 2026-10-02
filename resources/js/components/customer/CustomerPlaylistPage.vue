<script setup>
/**
 * The songs for the day, one card per moment in the order they come: akad,
 * entrance, makan beradab, potong kek, first walk, lagu latar, penutup.
 *
 * The suggestions below the form never add anything by themselves. Tapping one
 * fills the form in and brings it into view, because the same song is an
 * entrance for one couple and a first walk for another — only they can say.
 */
import { computed, nextTick, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';

const props = defineProps({
    moments: { type: Array, required: true },
    suggestions: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const form = ref(null);
const titleInput = ref(null);
const title = ref('');
const artist = ref('');
const search = ref('');

const matches = computed(() => {
    const needle = search.value.trim().toLowerCase();

    if (!needle) return props.suggestions;

    return props.suggestions.filter((song) => `${song.title} ${song.artist ?? ''}`.toLowerCase().includes(needle));
});

const useSuggestion = async (song) => {
    title.value = song.title;
    artist.value = song.artist ?? '';

    await nextTick();
    form.value?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    titleInput.value?.focus({ preventScroll: true });
};
</script>

<template>
    <p class="text-sm text-ink-muted">{{ $t('playlist.intro') }}</p>

    <form ref="form" :action="storeUrl" method="POST" class="mt-4 flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
        <input type="hidden" name="_token" :value="csrf">

        <div class="grid gap-3 sm:grid-cols-[12rem_1fr_1fr]">
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('playlist.momen') }}</span>
                <select name="moment" required class="nk-select rounded-xl border border-line bg-surface px-3 py-2.5 pr-9 text-sm focus:border-brand-400 focus:outline-none">
                    <option v-for="moment in moments" :key="moment.value" :value="moment.value">{{ moment.label }}</option>
                </select>
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('playlist.tajuk_lagu') }}</span>
                <input ref="titleInput" v-model="title" type="text" name="title" maxlength="120" required :placeholder="$t('playlist.contoh_tajuk')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('playlist.artis') }}</span>
                <input v-model="artist" type="text" name="artist" maxlength="120" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
        </div>

        <label class="flex min-w-0 flex-col gap-1.5">
            <span class="text-sm font-medium">{{ $t('playlist.nota') }}</span>
            <input type="text" name="notes" maxlength="255" :placeholder="$t('playlist.contoh_nota')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
        </label>

        <p v-for="message in Object.values(errors)" :key="message" class="text-xs text-brand-700">{{ message }}</p>

        <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('playlist.tambah_lagu') }}</button>
    </form>

    <section class="mt-8 grid gap-4 md:grid-cols-2">
        <div v-for="moment in moments" :key="moment.value" class="min-w-0 rounded-2xl border border-line bg-surface-raised">
            <div class="flex items-center justify-between gap-3 border-b border-line px-5 py-4">
                <h2 class="font-display text-lg font-semibold">{{ moment.label }}</h2>
                <span class="shrink-0 rounded-full bg-surface-muted px-2.5 py-0.5 text-xs font-medium text-ink-muted">{{ moment.songs.length }}</span>
            </div>

            <p v-if="!moment.songs.length" class="px-5 py-4 text-sm text-ink-muted">{{ $t('playlist.belum_ada_lagu') }}</p>

            <ol v-else class="divide-y divide-line">
                <li v-for="(song, at) in moment.songs" :key="song.id" class="flex items-start gap-3 px-5 py-3">
                    <span class="mt-0.5 w-5 shrink-0 text-xs font-semibold text-ink-muted">{{ at + 1 }}.</span>
                    <div class="min-w-0 flex-1 break-words">
                        <p class="font-medium">{{ song.title }}</p>
                        <p v-if="song.artist" class="text-xs text-ink-muted">{{ song.artist }}</p>
                        <p v-if="song.notes" class="mt-1 text-xs text-ink-muted italic">{{ song.notes }}</p>
                    </div>
                    <UiConfirm
                        :action="song.destroy_url"
                        method="DELETE"
                        tone="danger"
                        :title="$t('playlist.padam_lagu_ini')"
                        :message="song.title"
                        :confirm-label="$t('playlist.padam')"
                        trigger-class="shrink-0 text-xs font-medium text-ink-muted hover:text-brand-700"
                        :csrf="csrf"
                    >{{ $t('playlist.padam') }}</UiConfirm>
                </li>
            </ol>
        </div>
    </section>

    <section class="mt-10 rounded-2xl border border-line bg-surface-raised">
        <div class="flex flex-col gap-3 border-b border-line p-5 sm:flex-row sm:items-end sm:justify-between">
            <div class="min-w-0">
                <h2 class="font-display text-lg font-semibold">🎵 {{ $t('playlist.cadangan_lagu') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $t('playlist.cadangan_hint') }}</p>
            </div>
            <input v-model="search" type="search" :placeholder="$t('playlist.cari_lagu')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none sm:w-64">
        </div>

        <ul v-if="matches.length" class="grid max-h-[28rem] overflow-y-auto sm:grid-cols-2">
            <li v-for="song in matches" :key="`${song.title}-${song.artist}`" class="border-b border-line">
                <button type="button" class="flex w-full items-center gap-3 px-5 py-3 text-left transition hover:bg-brand-50" @click="useSuggestion(song)">
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium">{{ song.title }}</span>
                        <span v-if="song.artist" class="block truncate text-xs text-ink-muted">{{ song.artist }}</span>
                    </span>
                    <span class="shrink-0 rounded-full border border-line px-3 py-1 text-xs font-medium">{{ $t('playlist.pilih') }}</span>
                </button>
            </li>
        </ul>
        <p v-else class="p-5 text-sm text-ink-muted">{{ $t('playlist.tiada_padanan') }}</p>
    </section>
</template>
