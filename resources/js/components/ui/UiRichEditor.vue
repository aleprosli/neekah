<script setup>
/**
 * The article editor: TipTap, its toolbar, and the image upload that feeds it.
 *
 * The HTML travels in a hidden field so the form posts exactly as it always
 * did, and the server sanitises it again on save — nothing here is a security
 * boundary, it only shapes what an admin can easily produce.
 *
 * TipTap is loaded on demand: it is a large dependency and it exists on this
 * one page.
 */
import { onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import UiUploadProgress from './UiUploadProgress.vue';

const props = defineProps({
    modelValue: { type: String, default: '' },
    name: { type: String, default: 'body' },
    label: { type: String, default: 'Isi artikel' },
    help: { type: String, default: null },
    uploadUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    error: { type: String, default: null },
});

const emit = defineEmits(['update:modelValue']);

const TOOLBAR = [
    { command: 'h2', label: 'H2', title: 'Tajuk bahagian (H2)' },
    { command: 'h3', label: 'H3', title: 'Subtajuk (H3)' },
    { command: 'paragraph', label: '¶', title: 'Perenggan biasa' },
    null,
    { command: 'bold', label: 'B', title: 'Tebal' },
    { command: 'italic', label: 'I', title: 'Condong' },
    { command: 'underline', label: 'U', title: 'Garis bawah' },
    { command: 'strike', label: 'S', title: 'Garis tengah' },
    null,
    { command: 'bulletList', label: '• Senarai', title: 'Senarai titik' },
    { command: 'orderedList', label: '1. Senarai', title: 'Senarai bernombor' },
    { command: 'blockquote', label: '❝', title: 'Petikan' },
    { command: 'horizontalRule', label: '―', title: 'Garisan pemisah' },
    null,
    { command: 'link', label: '🔗 Pautan', title: 'Tambah atau buang pautan' },
    { command: 'image', label: '🖼️ Gambar', title: 'Masukkan gambar' },
    null,
    { command: 'undo', label: '↶', title: 'Buat asal' },
    { command: 'redo', label: '↷', title: 'Buat semula' },
];

const COMMANDS = {
    bold: (chain) => chain.toggleBold(),
    italic: (chain) => chain.toggleItalic(),
    underline: (chain) => chain.toggleUnderline(),
    strike: (chain) => chain.toggleStrike(),
    h2: (chain) => chain.toggleHeading({ level: 2 }),
    h3: (chain) => chain.toggleHeading({ level: 3 }),
    paragraph: (chain) => chain.setParagraph(),
    bulletList: (chain) => chain.toggleBulletList(),
    orderedList: (chain) => chain.toggleOrderedList(),
    blockquote: (chain) => chain.toggleBlockquote(),
    horizontalRule: (chain) => chain.setHorizontalRule(),
    undo: (chain) => chain.undo(),
    redo: (chain) => chain.redo(),
};

/** Which buttons light up for the formatting under the cursor. */
const ACTIVE = {
    bold: ['bold'],
    italic: ['italic'],
    underline: ['underline'],
    strike: ['strike'],
    h2: ['heading', { level: 2 }],
    h3: ['heading', { level: 3 }],
    bulletList: ['bulletList'],
    orderedList: ['orderedList'],
    blockquote: ['blockquote'],
    link: ['link'],
};

const element = ref(null);
const fileInput = ref(null);
const editor = shallowRef(null);
const words = ref(0);
const uploading = ref(false);
const uploadPercent = ref(0);
const uploadError = ref('');
const transactions = ref(0);

/** Google favours articles that cover a topic properly; the count helps judge that. */
const countWords = () => {
    words.value = editor.value.getText().trim().split(/\s+/).filter(Boolean).length;
};

const isActive = (command) => {
    // Read transactions so the toolbar re-renders as the cursor moves.
    transactions.value;

    const check = ACTIVE[command];

    return Boolean(editor.value && check && editor.value.isActive(...check));
};

const editLink = () => {
    const current = editor.value.getAttributes('link').href ?? '';
    const url = window.prompt('Pautan (URL). Kosongkan untuk buang pautan.', current);

    if (url === null) return;

    const chain = editor.value.chain().focus().extendMarkRange('link');
    (url.trim() === '' ? chain.unsetLink() : chain.setLink({ href: url.trim() })).run();
};

const uploadImage = (file) => {
    uploading.value = true;
    uploadPercent.value = 0;
    uploadError.value = '';

    const request = new XMLHttpRequest();
    request.open('POST', props.uploadUrl);
    request.setRequestHeader('X-CSRF-TOKEN', props.csrf);
    request.setRequestHeader('Accept', 'application/json');

    request.upload.addEventListener('progress', (event) => {
        if (event.lengthComputable) uploadPercent.value = (event.loaded / event.total) * 100;
    });

    request.addEventListener('load', () => {
        uploading.value = false;

        let payload = {};
        try {
            payload = JSON.parse(request.responseText);
        } catch {
            // Handled by the status check below.
        }

        if (request.status < 200 || request.status >= 300) {
            uploadError.value = payload.errors?.image?.[0] ?? payload.message ?? 'Gambar gagal dimuat naik.';
            return;
        }

        // Alt text is what Google Images and screen readers read; ask while the admin is here.
        const suggested = file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
        const alt = window.prompt('Terangkan gambar ini dalam beberapa perkataan (teks alt, penting untuk SEO).', suggested) ?? '';

        editor.value.chain().focus().setImage({ src: payload.url, alt }).run();
        countWords();
    });

    request.addEventListener('error', () => {
        uploading.value = false;
        uploadError.value = 'Sambungan terputus semasa memuat naik.';
    });

    const body = new FormData();
    body.append('image', file);
    request.send(body);
};

const run = (command) => {
    if (command === 'link') return editLink();
    if (command === 'image') return fileInput.value.click();

    COMMANDS[command]?.(editor.value.chain().focus()).run();
};

const onFileChosen = (event) => {
    const file = event.target.files?.[0];
    if (file) uploadImage(file);
    event.target.value = '';
};

// TipTap and ProseMirror are heavy, and this is the only page that needs them.
watch(element, async (node) => {
    if (!node || editor.value) return;

    const [{ Editor }, { default: Image }, { default: StarterKit }] = await Promise.all([
        import('@tiptap/core'),
        import('@tiptap/extension-image'),
        import('@tiptap/starter-kit'),
    ]);

    editor.value = new Editor({
        element: node,
        content: props.modelValue,
        extensions: [
            StarterKit.configure({
                heading: { levels: [2, 3, 4] },
                link: { openOnClick: false, autolink: true, defaultProtocol: 'https' },
            }),
            Image,
        ],
        editorProps: {
            attributes: { class: 'nk-prose nk-editor-surface', 'aria-label': 'Isi artikel' },
        },
        onUpdate: ({ editor: instance }) => {
            emit('update:modelValue', instance.isEmpty ? '' : instance.getHTML());
            countWords();
        },
        onTransaction: () => transactions.value++,
    });

    countWords();
}, { immediate: true });

onBeforeUnmount(() => editor.value?.destroy());
</script>

<template>
    <div class="flex min-w-0 flex-col gap-1.5">
        <span class="text-sm font-medium">{{ label }}</span>

        <div class="rounded-2xl border border-line bg-surface focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-400/40">
            <div class="sticky top-0 z-10 flex flex-wrap gap-1 rounded-t-2xl border-b border-line bg-surface-muted/90 p-2 backdrop-blur" role="toolbar" aria-label="Format teks">
                <template v-for="(button, at) in TOOLBAR" :key="at">
                    <span v-if="!button" class="mx-1 w-px self-stretch bg-line" aria-hidden="true"></span>
                    <button
                        v-else
                        type="button"
                        :title="button.title"
                        :aria-label="button.title"
                        :data-active="isActive(button.command) ? '' : undefined"
                        class="min-w-9 rounded-lg px-2.5 py-1.5 text-sm font-semibold transition hover:bg-surface"
                        @click="run(button.command)"
                    >{{ button.label }}</button>
                </template>
            </div>

            <div ref="element"></div>
        </div>

        <input type="hidden" :name="name" :value="modelValue">
        <input ref="fileInput" type="file" accept="image/jpeg,image/png,image/webp" class="hidden" @change="onFileChosen">

        <UiUploadProgress :uploading="uploading" :percent="Math.round(uploadPercent)" :error="uploadError" />

        <p class="flex justify-between gap-4 text-xs text-ink-muted">
            <span>{{ help }}</span>
            <span class="shrink-0">{{ words }} perkataan</span>
        </p>

        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
    </div>
</template>
