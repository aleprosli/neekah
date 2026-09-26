/**
 * The Neekah Kenangan upload queue on a guest's phone.
 *
 * Each file is prepared (a Basic album's photos are resized to its HD size
 * here, to spare the guest's data; HEIC becomes JPEG because the server
 * cannot read it), then a place is reserved on the server, the file goes
 * straight to storage with an XMLHttpRequest (fetch has no upload progress),
 * and the server is told it arrived. Three at a time; a failed one can be
 * retried by tapping it.
 *
 * `onDone` hears about each file; `onIdle` once, when the whole batch has
 * settled, which is when the page should look at the album again (asking
 * after every file sent one request per photo). `batch` is the progress of
 * what was picked together, for the screen that holds the guest until it
 * is all up.
 */
import { computed, reactive, ref } from 'vue';

const PARALLEL = 3;

let csrfToken = '';

export async function postJson(url, body, method = 'POST') {
    const response = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(body),
        credentials: 'same-origin',
    });

    const data = await response.json().catch(() => ({}));

    if (!response.ok) {
        const message = data?.errors ? Object.values(data.errors).flat()[0] : data?.message;
        throw new Error(message || `HTTP ${response.status}`);
    }

    return data;
}

/** Read how long a video runs, in whole seconds, without uploading it. */
function videoSeconds(file) {
    return new Promise((resolve) => {
        const video = document.createElement('video');
        video.preload = 'metadata';
        video.onloadedmetadata = () => {
            URL.revokeObjectURL(video.src);
            resolve(Number.isFinite(video.duration) ? Math.round(video.duration) : null);
        };
        video.onerror = () => resolve(null);
        video.src = URL.createObjectURL(file);
    });
}

/**
 * Redraw a photo at most `pixels` on its longest side as JPEG. Also turns
 * HEIC into JPEG on the phones that can decode it.
 */
async function toJpeg(file, pixels, quality = 0.85) {
    const bitmap = await createImageBitmap(file, { imageOrientation: 'from-image' });
    const scale = Math.min(1, pixels / Math.max(bitmap.width, bitmap.height));
    const canvas = document.createElement('canvas');
    canvas.width = Math.round(bitmap.width * scale);
    canvas.height = Math.round(bitmap.height * scale);
    canvas.getContext('2d').drawImage(bitmap, 0, 0, canvas.width, canvas.height);
    bitmap.close?.();

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, 'image/jpeg', quality));

    return new File([blob], file.name.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
}

function send(target, file, onProgress) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open(target.method, target.url);
        Object.entries(target.headers ?? {}).forEach(([name, value]) => xhr.setRequestHeader(name, value));
        xhr.upload.onprogress = (event) => event.lengthComputable && onProgress(event.loaded / event.total);
        xhr.onload = () => (xhr.status >= 200 && xhr.status < 300 ? resolve() : reject(new Error(`HTTP ${xhr.status}`)));
        xhr.onerror = () => reject(new Error('network'));
        xhr.send(file);
    });
}

export function useCameraUploads({ reserveUrl, limits, csrf, t, onDone, onIdle }) {
    csrfToken = csrf;
    const items = ref([]);
    let running = 0;

    const pump = () => {
        while (running < PARALLEL) {
            const next = items.value.find((item) => item.state === 'queued');
            if (!next) return;
            running++;
            run(next).finally(() => {
                running--;
                pump();
                if (running === 0 && !items.value.some((item) => item.state === 'queued')) onIdle?.();
            });
        }
    };

    async function prepare(item) {
        let file = item.file;
        const isVideo = file.type.startsWith('video/');

        if (isVideo) {
            if (!limits.allows_video) throw new Error(t('camera.error_no_video'));
            if (file.size > limits.video_max_megabytes * 1024 * 1024) throw new Error(t('camera.error_video_size', { mb: limits.video_max_megabytes }));
            const seconds = await videoSeconds(file);
            if (seconds !== null && seconds > limits.video_max_seconds) throw new Error(t('camera.error_video_length', { minutes: Math.round(limits.video_max_seconds / 60) }));

            return { file, type: 'video', seconds };
        }

        const heic = /hei[cf]/i.test(file.type) || /\.hei[cf]$/i.test(file.name);

        // Basic keeps HD, so resize on the phone; Pro sends the original
        // unless the phone produced HEIC.
        if (heic || limits.max_photos !== null) {
            try {
                file = await toJpeg(file, limits.max_photos !== null ? limits.photo_pixels : 8192);
            } catch {
                if (heic) throw new Error(t('camera.error_heic'));
            }
        }

        return { file, type: 'photo', seconds: null };
    }

    async function run(item) {
        try {
            item.state = 'preparing';
            const prepared = await prepare(item);
            item.state = 'uploading';

            const reservation = await postJson(reserveUrl, {
                type: prepared.type,
                mime: prepared.file.type,
                bytes: prepared.file.size,
                seconds: prepared.seconds,
            });

            await send(reservation.target, prepared.file, (share) => (item.progress = share));
            item.state = 'finishing';
            await postJson(reservation.complete, {});
            item.state = 'done';
            item.progress = 1;
            onDone?.();
        } catch (error) {
            item.state = 'failed';
            item.error = error.message === 'network' ? t('camera.error_network') : error.message;
        }
    }

    const add = (files) => {
        Array.from(files).forEach((file) => {
            items.value.unshift(reactive({ id: `${Date.now()}-${Math.random()}`, file, name: file.name, state: 'queued', progress: 0, error: null, preview: file.type.startsWith('image/') ? URL.createObjectURL(file) : null }));
        });
        pump();
    };

    const retry = (item) => {
        item.state = 'queued';
        item.error = null;
        item.progress = 0;
        pump();
    };

    const clearDone = () => {
        items.value = items.value.filter((item) => item.state !== 'done');
    };

    /** How far the current batch has got, counting a failed file as settled. */
    const batch = computed(() => {
        const total = items.value.length;
        const done = items.value.filter((item) => item.state === 'done').length;
        const failed = items.value.filter((item) => item.state === 'failed').length;
        const progress = total ? items.value.reduce((sum, item) => sum + (item.state === 'done' ? 1 : item.state === 'failed' ? 0 : item.progress * 0.95), 0) / total : 0;

        return { total, done, failed, settled: done + failed === total, progress };
    });

    return { items, add, retry, clearDone, batch };
}
