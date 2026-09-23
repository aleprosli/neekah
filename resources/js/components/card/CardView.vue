<script setup>
/**
 * A whole invitation card.
 *
 * One component renders the card a guest opens, the live preview inside the editor
 * and the thumbnails in the design gallery, so a couple is never shown something
 * their guests will not get. The design arrives as data — three canvases of layers
 * plus the sections the couple turned on — and the palette and type faces arrive as
 * CSS variables, which is why changing a colour in the editor repaints instantly
 * instead of asking the server to redraw anything.
 *
 * The card opens from a sealed cover. That is the Malay card it is imitating: you
 * are handed something closed and you open it.
 */
import { computed, defineAsyncComponent, onBeforeUnmount, onMounted, ref } from 'vue';
import CardClosing from './CardClosing.vue';
import CardContacts from './CardContacts.vue';
import CardCountdown from './CardCountdown.vue';
import CardGallery from './CardGallery.vue';
import CardGift from './CardGift.vue';
import CardItinerary from './CardItinerary.vue';
import CardLocation from './CardLocation.vue';
import CardRsvp from './CardRsvp.vue';
import CardScene from './CardScene.vue';
import CardWishes from './CardWishes.vue';

const CardMotionView = defineAsyncComponent(() => import('./CardMotionView.vue'));

const props = defineProps({
    canvases: { type: Array, default: () => [] },
    widgets: { type: Array, default: () => [] },
    content: { type: Object, default: () => ({}) },
    photos: { type: Object, default: () => ({}) },
    vars: { type: Object, default: () => ({}) },
    music: { type: Object, default: null },
    gate: { type: Object, default: () => ({ enabled: false }) },
    guest: { type: Object, default: null },
    labels: { type: Object, default: () => ({}) },
    preview: { type: Boolean, default: false },
    experience: { type: String, default: 'scroll' },
    /** A cover-only tile in the gallery: no gate, no music, no sections. */
    thumbnail: { type: Boolean, default: false },
    csrf: { type: String, default: null },
});

const WIDGETS = {
    countdown: CardCountdown,
    itinerary: CardItinerary,
    location: CardLocation,
    gallery: CardGallery,
    rsvp: CardRsvp,
    wishes: CardWishes,
    gift: CardGift,
    contacts: CardContacts,
    closing: CardClosing,
};

const sealed = ref(Boolean(props.gate?.enabled) && !props.thumbnail);
const playing = ref(false);
const audio = ref(null);
const column = ref(null);

const motion = computed(() => props.experience === 'motion' && !props.thumbnail);
const gated = computed(() => !motion.value && sealed.value);
const widgetBackground = computed(() => props.canvases
    .find((scene) => scene.key === 'event')?.layers.find((layer) => layer.widgetBackground)?.src ?? null);

/**
 * What each section is handed. Only the RSVP form posts anything, so only it is
 * given the token — the rest would print it as a stray attribute on their markup.
 */
const widgetProps = (widget) =>
    widget.key === 'rsvp'
        ? { widget, labels: props.labels, preview: props.preview, csrf: props.csrf }
        : { widget, labels: props.labels };

/** Opening the card is also the gesture that lets us start the music: a browser
 *  will not play audio until the visitor has asked for something. */
const openCard = async () => {
    sealed.value = false;

    if (props.music && audio.value) {
        try {
            await audio.value.play();
            playing.value = true;
        } catch {
            playing.value = false;
        }
    }
};

const toggleMusic = async () => {
    if (!audio.value) return;

    if (playing.value) {
        audio.value.pause();
        playing.value = false;
        return;
    }

    try {
        await audio.value.play();
        playing.value = true;
    } catch {
        playing.value = false;
    }
};

/** Sections rise as they come into view; a card that arrives all at once reads as
 *  a web page, and someone who asked for less motion gets none. */
let observer = null;

onMounted(() => {
    if (props.thumbnail || motion.value || !('IntersectionObserver' in window)) return;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    observer = new IntersectionObserver(
        (entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -12% 0px', threshold: 0.08 },
    );

    column.value?.querySelectorAll('[data-reveal]').forEach((element) => observer.observe(element));
});

onBeforeUnmount(() => observer?.disconnect());

</script>

<template>
    <div class="nkc" :class="{ 'nkc-is-preview': preview, 'nkc-is-thumb': thumbnail, 'nkc-is-gated': gated, 'nkc-is-motion': motion, 'nkc-has-widget-artwork': widgetBackground }" :style="{ ...vars, '--nkc-widget-artwork': widgetBackground ? `url('${widgetBackground}')` : undefined }">
        <CardMotionView
            v-if="motion"
            :canvases="canvases"
            :widgets="widgets"
            :content="content"
            :photos="photos"
            :gate="gate"
            :labels="labels"
            :preview="preview"
            :auto-open="!music"
            :csrf="csrf"
            @opened="openCard"
        >
            <template #music>
                <button
                    v-if="music"
                    type="button"
                    class="nkc-motion-music"
                    :aria-label="playing ? labels.music_off : labels.music_on"
                    @click="toggleMusic()"
                >
                    <svg v-if="!playing" class="nkc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18V6l10-2v12" /><circle cx="6" cy="18" r="3" /><circle cx="16" cy="16" r="3" />
                    </svg>
                    <span v-else class="nkc-bars" aria-hidden="true"><i /><i /><i /></span>
                </button>
            </template>
        </CardMotionView>
        <div v-else ref="column" class="nkc-column">
            <CardScene
                v-for="(scene, index) in canvases"
                :key="scene.key"
                :scene="scene"
                :content="content"
                :photos="photos"
                :preview="preview"
                :first="index === 0"
                :data-reveal="index > 0 ? '' : null"
            >
                <button
                    v-if="index === 0 && gated"
                    type="button"
                    class="nkc-open"
                    data-gate
                    @click="openCard()"
                >
                    <svg class="nkc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" />
                    </svg>
                    {{ gate.label }}
                </button>
            </CardScene>

            <template v-if="!thumbnail">
                <component
                    :is="WIDGETS[widget.key]"
                    v-for="widget in widgets"
                    :key="widget.key"
                    v-bind="widgetProps(widget)"
                    data-reveal
                />
            </template>
        </div>

        <template v-if="music && !thumbnail">
            <audio ref="audio" :src="music.url" loop preload="none" />
            <div v-if="!motion" class="nkc-music-wrap">
                <button
                    type="button"
                    class="nkc-music"
                    :class="{ playing }"
                    :aria-label="playing ? labels.music_off : labels.music_on"
                    @click="toggleMusic()"
                >
                    <svg v-if="!playing" class="nkc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 18V6l10-2v12" /><circle cx="6" cy="18" r="3" /><circle cx="16" cy="16" r="3" />
                    </svg>
                    <span v-else class="nkc-bars" aria-hidden="true"><i /><i /><i /></span>
                </button>
            </div>
        </template>
    </div>
</template>
