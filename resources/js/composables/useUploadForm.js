/**
 * Submitting a form that carries files, with a progress bar.
 *
 * A native form post gives no progress at all: the page simply sits there while
 * a phone photo crawls up the connection. This posts the same FormData over
 * XHR so the bytes can be counted, then follows where the server says to go.
 *
 * The server answers such a request with JSON ({ redirect }), because a 302 is
 * followed invisibly by XHR and would eat the flash message meant for the page
 * the visitor lands on.
 */
import { computed, ref } from 'vue';

export function useUploadForm() {
    const uploading = ref(false);
    const progress = ref(0);
    const error = ref('');

    const percent = computed(() => Math.round(progress.value));

    /** Whether this form actually has a file to upload right now. */
    const hasFiles = (form) =>
        [...new FormData(form).values()].some((value) => value instanceof File && value.size > 0);

    const submit = (event) => {
        const form = event.target;

        // No file chosen: let the browser post it the ordinary way.
        if (!hasFiles(form)) {
            return;
        }

        event.preventDefault();
        uploading.value = true;
        progress.value = 0;
        error.value = '';

        const request = new XMLHttpRequest();
        request.open(form.method || 'POST', form.action);
        request.setRequestHeader('Accept', 'application/json');
        request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        request.upload.addEventListener('progress', (progressEvent) => {
            if (progressEvent.lengthComputable) {
                progress.value = (progressEvent.loaded / progressEvent.total) * 100;
            }
        });

        request.addEventListener('load', () => {
            if (request.status >= 200 && request.status < 300) {
                let payload = {};
                try {
                    payload = JSON.parse(request.responseText);
                } catch {
                    // A plain redirect is fine too; reload and let the page render.
                }

                window.location.assign(payload.redirect || window.location.href);
                return;
            }

            uploading.value = false;

            if (request.status === 422) {
                const { errors = {} } = JSON.parse(request.responseText || '{}');
                error.value = Object.values(errors).flat().join(' ');
                return;
            }

            error.value =
                request.status === 413
                    ? 'Fail terlalu besar untuk server ini.'
                    : `Muat naik gagal (${request.status}). Sila cuba lagi.`;
        });

        request.addEventListener('error', () => {
            uploading.value = false;
            error.value = 'Sambungan terputus semasa memuat naik. Sila cuba lagi.';
        });

        request.send(new FormData(form));
    };

    return { uploading, progress, percent, error, submit };
}
