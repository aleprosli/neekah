<script setup>
/**
 * One layer of a designed canvas: a line of type, a photo slot, a drawn shape or a
 * tinted SVG ornament. Everything about how it is placed comes from
 * resources/js/card/layerStyle.js, so the same layer renders identically in the
 * editor's preview, in a gallery thumbnail and on the card a guest opens.
 */
import { computed } from 'vue';
import { boxStyle, cq, fillStyle, frameStyle, ornamentStyle, textStyle } from '../../card/layerStyle.js';
import { resolveTokens } from '../../card/tokens.js';

const props = defineProps({
    scene: { type: Object, required: true },
    layer: { type: Object, required: true },
    content: { type: Object, default: () => ({}) },
    /** slot key => photo url, for layers bound to one of the couple's photos. */
    photos: { type: Object, default: () => ({}) },
    /** A preview shows the artwork's placeholder where a photo is missing; a live
     *  card shows nothing, because a guest must not see our scaffolding. */
    preview: { type: Boolean, default: false },
    eager: { type: Boolean, default: false },
});

const box = computed(() => boxStyle(props.scene, props.layer));

const text = computed(() => resolveTokens(props.layer.text, props.content));

/** The couple's photo, or the design's demo art while previewing. */
const photo = computed(() => {
    const bound = props.layer.bind ? props.photos[props.layer.bind] : null;

    if (bound) return bound;

    return props.layer.placeholder && !props.preview ? null : props.layer.src;
});

const imageStyle = computed(() => {
    const style = {
        objectFit: props.layer.fit,
        objectPosition: `${props.layer.fx}% ${props.layer.fy}%`,
    };

    if (props.layer.zoom > 100) {
        style.transform = `scale(${props.layer.zoom / 100})`;
        style.transformOrigin = `${props.layer.fx}% ${props.layer.fy}%`;
    }

    return style;
});
</script>

<template>
    <div v-if="!layer.visible" />

    <div v-else-if="layer.type === 'text'" class="nkc-layer nkc-layer-text" :style="{ ...box, ...textStyle(scene, layer) }">
        <span>{{ text }}</span>
    </div>

    <template v-else-if="layer.type === 'image'">
        <div
            v-if="photo && layer.tile > 0"
            class="nkc-layer"
            :style="{ ...box, background: `url('${photo}') 0 0/${cq(layer.tile, scene.width)} repeat` }"
        />
        <div v-else-if="photo" class="nkc-layer nkc-layer-image" :class="{ 'nkc-layer-artwork': layer.motion, 'nkc-layer-gif': layer.widgetAnimation }" :style="{ ...box, ...frameStyle(scene, layer) }">
            <img :src="photo" alt="" :loading="eager ? 'eager' : 'lazy'" decoding="async" :style="imageStyle">
        </div>
        <div v-else-if="preview && layer.bind" class="nkc-layer nkc-layer-empty" :style="{ ...box, ...frameStyle(scene, layer) }">
            <svg class="size-1/5 max-h-10 max-w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <rect x="3" y="4" width="18" height="16" rx="2" /><circle cx="9" cy="10" r="2" /><path d="m4 19 5-5 4 4 3-3 4 4" />
            </svg>
        </div>
    </template>

    <div v-else-if="layer.type === 'ornament' && layer.src" class="nkc-layer" :style="{ ...box, ...ornamentStyle(scene, layer) }" />

    <div v-else-if="layer.type === 'shape'" class="nkc-layer" :style="{ ...box, ...frameStyle(scene, layer), background: fillStyle(layer) }" />
</template>
