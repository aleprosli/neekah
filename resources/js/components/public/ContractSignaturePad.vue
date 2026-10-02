<script setup>
/**
 * A signature drawn with a finger or a mouse, posted with the contract's
 * sign form as a PNG data URL in a hidden input. The labels come as props:
 * the printable document shell ships no translation groups.
 *
 * The canvas is drawn at the screen's pixel density so the line is sharp,
 * and exported no wider than 1000px so the upload stays small.
 */
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    name: { type: String, default: 'signature' },
    label: { type: String, required: true },
    hint: { type: String, required: true },
    clearLabel: { type: String, required: true },
    error: { type: String, default: null },
});

const canvas = ref(null);
const value = ref('');
const drawn = ref(false);
let context = null;
let drawing = false;
let last = null;
let drawnWidth = 0;

/** Redraws only when the width changes: a phone's toolbar sliding away fires resize too. */
const resize = () => {
    const element = canvas.value;
    const ratio = window.devicePixelRatio || 1;
    const { width, height } = element.getBoundingClientRect();

    if (width === drawnWidth) return;
    drawnWidth = width;

    element.width = Math.round(width * ratio);
    element.height = Math.round(height * ratio);
    context = element.getContext('2d');
    context.scale(ratio, ratio);
    context.lineWidth = 2.2;
    context.lineCap = 'round';
    context.lineJoin = 'round';
    context.strokeStyle = '#111';
    clear();
};

const point = (event) => {
    const rect = canvas.value.getBoundingClientRect();

    return { x: event.clientX - rect.left, y: event.clientY - rect.top };
};

const start = (event) => {
    event.preventDefault();
    canvas.value.setPointerCapture(event.pointerId);
    drawing = true;
    last = point(event);
    context.beginPath();
    context.arc(last.x, last.y, 1.1, 0, Math.PI * 2);
    context.fillStyle = '#111';
    context.fill();
};

const move = (event) => {
    if (!drawing) return;
    event.preventDefault();
    const next = point(event);
    context.beginPath();
    context.moveTo(last.x, last.y);
    context.lineTo(next.x, next.y);
    context.stroke();
    last = next;
    drawn.value = true;
};

const end = () => {
    if (!drawing) return;
    drawing = false;
    if (drawn.value) value.value = exportPng();
};

const exportPng = () => {
    const source = canvas.value;
    const scale = Math.min(1, 1000 / source.width);
    const out = document.createElement('canvas');
    out.width = Math.round(source.width * scale);
    out.height = Math.round(source.height * scale);
    out.getContext('2d').drawImage(source, 0, 0, out.width, out.height);

    return out.toDataURL('image/png');
};

const clear = () => {
    if (!context) return;
    context.clearRect(0, 0, canvas.value.width, canvas.value.height);
    drawn.value = false;
    value.value = '';
};

onMounted(() => {
    resize();
    window.addEventListener('resize', resize);
});
onBeforeUnmount(() => window.removeEventListener('resize', resize));
</script>

<template>
    <div class="flex flex-col gap-1.5">
        <div class="flex items-center justify-between gap-3">
            <span class="text-sm font-medium">{{ label }}</span>
            <button type="button" class="text-xs font-medium text-ink-muted underline-offset-4 hover:text-ink hover:underline" @click="clear">{{ clearLabel }}</button>
        </div>
        <canvas
            ref="canvas"
            class="h-40 w-full touch-none rounded-xl border bg-white"
            :class="error ? 'border-brand-400' : 'border-line'"
            @pointerdown="start"
            @pointermove="move"
            @pointerup="end"
            @pointercancel="end"
            @pointerleave="end"
        ></canvas>
        <input type="hidden" :name="name" :value="value">
        <span v-if="error" class="text-xs text-brand-700">{{ error }}</span>
        <span v-else class="text-xs text-ink-muted">{{ hint }}</span>
    </div>
</template>
