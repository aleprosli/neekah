<script setup>
/**
 * Write a Threads post by hand, or change one the planner wrote. A panel over
 * the page (a sheet from the bottom on a phone), posting an ordinary
 * multipart form, so the server's validation and flash work as they do on
 * every other admin form.
 *
 * The counter is in bytes, as Threads counts: an emoji takes four of the 500.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    /** null for a new post. */
    post: { type: Object, default: null },
    types: { type: Array, required: true },
    limits: { type: Object, required: true },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const emit = defineEmits(['close']);

/** An hour from now, on the minute, as datetime-local wants it. */
const inAnHour = () => {
    const at = new Date(Date.now() + 60 * 60 * 1000);
    at.setSeconds(0, 0);
    const pad = (value) => String(value).padStart(2, '0');

    return `${at.getFullYear()}-${pad(at.getMonth() + 1)}-${pad(at.getDate())}T${pad(at.getHours())}:${pad(at.getMinutes())}`;
};

const form = ref({
    type: props.post?.type ?? 'engagement',
    scheduled_at: props.post?.scheduled_at ?? inAnHour(),
    text: props.post?.text ?? '',
    link_url: props.post?.link_url ?? '',
    topic_tag: props.post?.topic_tag ?? '',
    poll_options: [...(props.post?.poll_options ?? ['', '', '', ''])],
});

const removeImage = ref(false);
const chosenImage = ref(null);

const encoder = new TextEncoder();
const bytes = computed(() => encoder.encode(form.value.text).length);
const tooLong = computed(() => bytes.value > props.limits.text_bytes);

const hasImage = computed(() => Boolean(chosenImage.value) || (props.post?.has_image && !removeImage.value));

const pickImage = (event) => {
    chosenImage.value = event.target.files?.[0] ?? null;
};

const onKey = (event) => {
    if (event.key === 'Escape') {
        emit('close');
    }
};

onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));

const inputClass = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none';
</script>

<template>
    <div class="fixed inset-0 z-50 flex items-end justify-center bg-ink/40 sm:items-center sm:p-6" @click.self="emit('close')">
        <form
            :action="post ? post.update_url : storeUrl"
            method="POST"
            enctype="multipart/form-data"
            class="flex max-h-[92vh] w-full max-w-2xl min-w-0 flex-col overflow-y-auto rounded-t-3xl bg-surface-raised shadow-xl sm:rounded-3xl"
            role="dialog"
            aria-modal="true"
            :aria-label="post ? $t('admin_threads.edit_post') : $t('admin_threads.tulis_post')"
        >
            <input type="hidden" name="_token" :value="csrf">
            <input v-if="post" type="hidden" name="_method" value="PUT">

            <header class="flex items-center justify-between gap-3 border-b border-line px-5 py-4 sm:px-6">
                <h2 class="font-display text-lg font-semibold">{{ post ? $t('admin_threads.edit_post') : $t('admin_threads.tulis_post') }}</h2>
                <button type="button" class="flex size-9 items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-ink" :aria-label="$t('admin_threads.tutup')" @click="emit('close')">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                </button>
            </header>

            <div class="flex min-w-0 flex-col gap-4 px-5 py-5 sm:px-6">
                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_threads.jenis') }}</span>
                        <select v-model="form.type" name="type" class="nk-select rounded-xl border border-line bg-surface py-2.5 pr-10 pl-4 text-sm focus:border-brand-400 focus:outline-none">
                            <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                        </select>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_threads.masa_post') }}</span>
                        <input v-model="form.scheduled_at" type="datetime-local" name="scheduled_at" required :class="inputClass">
                        <span v-if="errors.scheduled_at" class="text-xs text-brand-700">{{ errors.scheduled_at }}</span>
                    </label>
                </div>

                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="flex items-center justify-between gap-2 text-sm font-medium">
                        {{ $t('admin_threads.teks') }}
                        <span :class="['text-xs font-normal', tooLong ? 'text-red-700' : 'text-ink-muted']">{{ bytes }} / {{ limits.text_bytes }}</span>
                    </span>
                    <textarea v-model="form.text" name="text" rows="7" required :class="inputClass"></textarea>
                    <span v-if="errors.text" class="text-xs text-brand-700">{{ errors.text }}</span>
                </label>

                <div class="flex min-w-0 flex-col gap-2">
                    <span class="text-sm font-medium">{{ $t('admin_threads.gambar') }}</span>
                    <div v-if="post?.image && !removeImage" class="flex items-center gap-3">
                        <img :src="post.image" alt="" class="size-20 rounded-xl object-cover ring-1 ring-line">
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="removeImage" type="checkbox" class="accent-brand-600">{{ $t('admin_threads.buang_gambar') }}</label>
                    </div>
                    <input v-if="removeImage" type="hidden" name="remove_image" value="1">
                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm" @change="pickImage">
                    <span class="text-xs text-ink-muted">{{ $t('admin_threads.gambar_bantuan') }}</span>
                    <span v-if="errors.image" class="text-xs text-brand-700">{{ errors.image }}</span>
                </div>

                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_threads.pautan') }}</span>
                        <input v-model="form.link_url" type="url" name="link_url" placeholder="https://neekah.my/…" :class="inputClass">
                        <span class="text-xs text-ink-muted">{{ hasImage ? $t('admin_threads.pautan_dengan_gambar') : $t('admin_threads.pautan_bantuan') }}</span>
                        <span v-if="errors.link_url" class="text-xs text-brand-700">{{ errors.link_url }}</span>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_threads.topik') }}</span>
                        <input v-model="form.topic_tag" type="text" name="topic_tag" maxlength="50" :placeholder="$t('admin_threads.topik_contoh')" :class="inputClass">
                        <span v-if="errors.topic_tag" class="text-xs text-brand-700">{{ errors.topic_tag }}</span>
                    </label>
                </div>

                <fieldset class="flex min-w-0 flex-col gap-2" :disabled="hasImage">
                    <legend class="text-sm font-medium">{{ $t('admin_threads.undian') }}</legend>
                    <p class="text-xs text-ink-muted">{{ hasImage ? $t('admin_threads.undian_tiada_gambar') : $t('admin_threads.undian_bantuan', { max: limits.poll_option }) }}</p>
                    <div class="grid min-w-0 gap-2 sm:grid-cols-2">
                        <input
                            v-for="(option, index) in form.poll_options"
                            :key="index"
                            v-model="form.poll_options[index]"
                            type="text"
                            name="poll_options[]"
                            :maxlength="limits.poll_option"
                            :placeholder="$t('admin_threads.pilihan_n', { n: index + 1 })"
                            :class="[inputClass, 'disabled:opacity-50']"
                        >
                    </div>
                </fieldset>
            </div>

            <footer class="flex flex-wrap items-center justify-end gap-2 border-t border-line px-5 py-4 sm:px-6">
                <button type="button" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="emit('close')">{{ $t('admin_threads.batal') }}</button>
                <button type="submit" :disabled="tooLong" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50">{{ $t('admin_threads.simpan_jadual') }}</button>
            </footer>
        </form>
    </div>
</template>
