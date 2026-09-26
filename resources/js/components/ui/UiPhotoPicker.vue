<script setup>
/**
 * A file input that shows what was chosen and lets one be taken out again.
 *
 * The browser's own multi-file input cannot remove a single file, so the list
 * is held here and written back into a real <input type="file"> through a
 * DataTransfer. The form is still an ordinary multipart form submit; this only
 * changes what the person sees before they send it.
 */
import { onBeforeUnmount, ref, useTemplateRef } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    max: { type: Number, default: 6 },
    label: { type: String, default: 'Gambar' },
});

const input = useTemplateRef('input');
const chosen = ref([]);
const notice = ref('');

/** Object URLs are revoked by hand; the browser holds the file until they are. */
const release = () => chosen.value.forEach((photo) => URL.revokeObjectURL(photo.preview));

onBeforeUnmount(release);

const syncInput = () => {
    const transfer = new DataTransfer();
    chosen.value.forEach((photo) => transfer.items.add(photo.file));
    input.value.files = transfer.files;
};

const add = (event) => {
    const incoming = [...event.target.files];
    const room = props.max - chosen.value.length;

    notice.value = incoming.length > room ? `Hanya ${props.max} gambar dibenarkan.` : '';

    chosen.value.push(
        ...incoming.slice(0, Math.max(room, 0)).map((file) => ({
            file,
            preview: URL.createObjectURL(file),
            key: `${file.name}-${file.lastModified}-${file.size}`,
        })),
    );

    syncInput();
};

const remove = (at) => {
    URL.revokeObjectURL(chosen.value[at].preview);
    chosen.value.splice(at, 1);
    notice.value = '';
    syncInput();
};
</script>

<template>
    <div class="flex min-w-0 flex-col gap-2">
        <span class="text-xs font-semibold tracking-wide uppercase">
            {{ label }}
            <span class="font-normal normal-case opacity-70">{{ $t('common.optional_up_to', { count: max }) }}</span>
        </span>

        <ul v-if="chosen.length" class="flex flex-wrap gap-2">
            <li v-for="(photo, at) in chosen" :key="photo.key" class="relative">
                <img :src="photo.preview" alt="" class="size-20 rounded-lg object-cover" />
                <button
                    type="button"
                    class="absolute -top-1.5 -right-1.5 flex size-6 items-center justify-center rounded-full bg-ink text-xs text-surface shadow"
                    @click="remove(at)"
                >
                    <span aria-hidden="true">×</span>
                    <span class="sr-only">Buang gambar {{ at + 1 }}</span>
                </button>
            </li>
        </ul>

        <label
            v-show="chosen.length < max"
            class="flex w-full cursor-pointer items-center justify-center rounded-xl border border-dashed border-line px-4 py-3 text-sm text-ink-muted transition hover:border-brand-400 sm:w-auto sm:self-start sm:px-6"
        >
            <span>{{ chosen.length ? $t('portfolio.tambah_gambar_lagi') : $t('portfolio.pilih_gambar') }}</span>
            <input
                ref="input"
                type="file"
                :name="name"
                multiple
                accept="image/jpeg,image/png,image/webp"
                class="sr-only"
                @change="add"
            />
        </label>

        <p v-if="notice" class="text-xs text-brand-700">{{ notice }}</p>
    </div>
</template>
