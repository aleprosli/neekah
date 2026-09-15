import { Editor } from '@tiptap/core';
import Image from '@tiptap/extension-image';
import StarterKit from '@tiptap/starter-kit';

/**
 * The blog article editor: TipTap mounted on every [data-rich-editor].
 *
 * The Blade form supplies the pieces, found by data attribute:
 *   [data-editor-content]    the editable area
 *   [data-editor-input]      hidden field that receives the HTML on every change
 *   [data-editor-command=x]  toolbar buttons
 *   [data-editor-file]       hidden file picker used by the image button
 *   [data-editor-status]     word count, upload progress and upload errors
 * and data-upload-url on the root, where an image is posted.
 *
 * The server sanitises the HTML again on save, so nothing here is a security
 * boundary; it only shapes what an admin can easily produce.
 */

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

export function mountEditors() {
    document.querySelectorAll('[data-rich-editor]').forEach(mount);
}

function mount(root) {
    const input = root.querySelector('[data-editor-input]');
    const fileInput = root.querySelector('[data-editor-file]');
    const status = root.querySelector('[data-editor-status]');
    const buttons = root.querySelectorAll('[data-editor-command]');
    const token = root.closest('form')?.querySelector('input[name="_token"]')?.value ?? '';

    const editor = new Editor({
        element: root.querySelector('[data-editor-content]'),
        content: input.value,
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
        onUpdate: () => {
            input.value = editor.isEmpty ? '' : editor.getHTML();
            showWordCount();
        },
        onTransaction: () => refreshToolbar(),
    });

    function refreshToolbar() {
        buttons.forEach((button) => {
            const check = ACTIVE[button.dataset.editorCommand];
            button.toggleAttribute('data-active', Boolean(check && editor.isActive(...check)));
        });
    }

    /** Google favours articles that cover a topic properly; the count helps an admin judge that. */
    function showWordCount() {
        const words = editor.getText().trim().split(/\s+/).filter(Boolean).length;
        status.textContent = `${words} perkataan`;
    }

    function editLink() {
        const current = editor.getAttributes('link').href ?? '';
        const url = window.prompt('Pautan (URL). Kosongkan untuk buang pautan.', current);

        if (url === null) {
            return;
        }

        const chain = editor.chain().focus().extendMarkRange('link');
        (url.trim() === '' ? chain.unsetLink() : chain.setLink({ href: url.trim() })).run();
    }

    async function uploadImage(file) {
        const body = new FormData();
        body.append('image', file);
        status.textContent = 'Memuat naik gambar…';

        try {
            const response = await fetch(root.dataset.uploadUrl, {
                method: 'POST',
                body,
                headers: { 'X-CSRF-TOKEN': token, Accept: 'application/json' },
            });
            const json = await response.json();

            if (!response.ok) {
                throw new Error(json.errors?.image?.[0] ?? json.message ?? 'Gambar gagal dimuat naik.');
            }

            // Alt text is what Google Images and screen readers read; ask for it while the admin is here.
            const suggested = file.name.replace(/\.[^.]+$/, '').replace(/[-_]+/g, ' ');
            const alt = window.prompt('Terangkan gambar ini dalam beberapa perkataan (teks alt, penting untuk SEO).', suggested) ?? '';

            editor.chain().focus().setImage({ src: json.url, alt }).run();
            showWordCount();
        } catch (error) {
            status.textContent = error.message;
        }
    }

    buttons.forEach((button) =>
        button.addEventListener('click', () => {
            const name = button.dataset.editorCommand;

            if (name === 'link') {
                editLink();
            } else if (name === 'image') {
                fileInput.click();
            } else {
                COMMANDS[name]?.(editor.chain().focus()).run();
            }
        }),
    );

    fileInput.addEventListener('change', () => {
        if (fileInput.files[0]) {
            uploadImage(fileInput.files[0]);
        }

        fileInput.value = '';
    });

    showWordCount();
}
