<script setup>
/**
 * One designed canvas — the cover, the invitation or the event details.
 *
 * The stage is a container query context, which is what makes every length inside
 * it (set in cqw) scale with the card rather than with the viewport.
 */
import CardLayer from './CardLayer.vue';

const props = defineProps({
    scene: { type: Object, required: true },
    content: { type: Object, default: () => ({}) },
    photos: { type: Object, default: () => ({}) },
    preview: { type: Boolean, default: false },
    first: { type: Boolean, default: false },
});
</script>

<template>
    <section
        :id="`scene-${scene.key}`"
        class="nkc-scene"
        :data-scene="scene.key"
        :style="{ aspectRatio: `${scene.width}/${scene.height}` }"
    >
        <CardLayer
            v-for="layer in scene.layers"
            :key="layer.id"
            :scene="scene"
            :layer="layer"
            :content="content"
            :photos="photos"
            :preview="preview"
            :eager="first"
        />
        <slot />
    </section>
</template>
