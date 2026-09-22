<script setup>
/**
 * The couple's photos, and a lightbox to see one properly. Nothing here is
 * pinch-to-zoom guesswork: a tap opens the full image, Escape and the arrows move.
 */
import { onBeforeUnmount, ref, watch } from 'vue';
import CardSection from './CardSection.vue';

const props = defineProps({
    widget: { type: Object, required: true },
});

const open = ref(null);

const show = (index) => (open.value = index);
const close = () => (open.value = null);
const step = (by) => {
    const count = props.widget.photos.length;

    open.value = (open.value + by + count) % count;
};

const onKey = (event) => {
    if (open.value === null) return;
    if (event.key === 'Escape') close();
    if (event.key === 'ArrowRight') step(1);
    if (event.key === 'ArrowLeft') step(-1);
};

watch(open, (value) => {
    if (value === null) {
        window.removeEventListener('keydown', onKey);
    } else {
        window.addEventListener('keydown', onKey);
    }
});

onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <CardSection :heading="widget.heading">
        <div class="nkc-gallery">
            <button v-for="(photo, index) in widget.photos" :key="photo.url" type="button" class="nkc-gallery-item" @click="show(index)">
                <img :src="photo.url" :alt="photo.caption" loading="lazy" decoding="async">
            </button>
        </div>

        <Teleport to="body">
            <div v-if="open !== null" class="nkc-lightbox" @click.self="close()">
                <img :src="widget.photos[open].url" :alt="widget.photos[open].caption">
                <button type="button" class="nkc-lb-close" aria-label="Tutup" @click="close()">&times;</button>
                <template v-if="widget.photos.length > 1">
                    <button type="button" class="nkc-lb-prev" aria-label="Sebelum" @click="step(-1)">&lsaquo;</button>
                    <button type="button" class="nkc-lb-next" aria-label="Seterusnya" @click="step(1)">&rsaquo;</button>
                </template>
            </div>
        </Teleport>
    </CardSection>
</template>
