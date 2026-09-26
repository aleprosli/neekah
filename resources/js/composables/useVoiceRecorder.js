/**
 * A short voice wish recorded on a guest's phone.
 *
 * MediaRecorder picks what the browser can write: Opus in WebM on Chrome and
 * Android, AAC in MP4 on Safari, Ogg on Firefox. The server reads the first
 * bytes to check which it got, so the type claimed here does not matter.
 * Recording stops by itself at `maxSeconds`.
 */
import { onBeforeUnmount, ref } from 'vue';

const TYPES = ['audio/webm;codecs=opus', 'audio/webm', 'audio/mp4', 'audio/ogg;codecs=opus'];

export function useVoiceRecorder({ maxSeconds }) {
    const supported = typeof window !== 'undefined' && 'MediaRecorder' in window && !!navigator.mediaDevices?.getUserMedia;
    const state = ref('idle'); // idle | recording | recorded | denied
    const seconds = ref(0);
    const blob = ref(null);
    const url = ref(null);

    let recorder = null;
    let stream = null;
    let timer = null;
    let startedAt = 0;

    const release = () => {
        clearInterval(timer);
        stream?.getTracks().forEach((track) => track.stop());
        stream = null;
    };

    const reset = () => {
        if (url.value) URL.revokeObjectURL(url.value);
        blob.value = null;
        url.value = null;
        seconds.value = 0;
        state.value = 'idle';
    };

    const stop = () => {
        if (recorder && recorder.state !== 'inactive') recorder.stop();
    };

    const start = async () => {
        if (!supported) return;
        reset();

        try {
            stream = await navigator.mediaDevices.getUserMedia({ audio: { echoCancellation: true, noiseSuppression: true } });
        } catch {
            state.value = 'denied';
            return;
        }

        const mimeType = TYPES.find((type) => MediaRecorder.isTypeSupported?.(type));
        recorder = new MediaRecorder(stream, mimeType ? { mimeType } : undefined);
        const chunks = [];

        recorder.ondataavailable = (event) => event.data.size && chunks.push(event.data);
        recorder.onstop = () => {
            release();
            seconds.value = Math.max(1, Math.min(maxSeconds, Math.round((Date.now() - startedAt) / 1000)));
            blob.value = new Blob(chunks, { type: recorder.mimeType || mimeType || 'audio/webm' });
            url.value = URL.createObjectURL(blob.value);
            state.value = 'recorded';
        };

        startedAt = Date.now();
        recorder.start(250);
        state.value = 'recording';
        timer = setInterval(() => {
            seconds.value = Math.floor((Date.now() - startedAt) / 1000);
            if (seconds.value >= maxSeconds) stop();
        }, 250);
    };

    onBeforeUnmount(() => {
        stop();
        release();
        if (url.value) URL.revokeObjectURL(url.value);
    });

    return { supported, state, seconds, blob, url, start, stop, reset };
}
