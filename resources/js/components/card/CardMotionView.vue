<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
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

const props = defineProps({
    canvases: { type: Array, default: () => [] },
    widgets: { type: Array, default: () => [] },
    content: { type: Object, default: () => ({}) },
    photos: { type: Object, default: () => ({}) },
    gate: { type: Object, default: () => ({ enabled: false }) },
    labels: { type: Object, default: () => ({}) },
    preview: { type: Boolean, default: false },
    autoOpen: { type: Boolean, default: true },
    csrf: { type: String, default: null },
});

const emit = defineEmits(['opened']);

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

const manualWidgets = new Set(['location', 'gallery', 'rsvp', 'gift', 'contacts']);
const slides = computed(() => [
    ...props.canvases.map((scene) => ({ kind: 'scene', key: scene.key, data: scene })),
    ...props.widgets.map((widget) => ({ kind: 'widget', key: widget.key, data: widget })),
]);
const activeIndex = ref(0);
const opened = ref(false);
const revealed = ref(false);
const paused = ref(false);
const reducedMotion = ref(false);
const elapsed = ref(0);
const activeSlide = computed(() => slides.value[activeIndex.value]);
const widgetForeground = computed(() => props.canvases
    .find((scene) => scene.key === 'event')?.layers.find((layer) => layer.widgetForeground)?.src ?? null);
const widgetAnimation = computed(() => props.canvases
    .find((scene) => scene.key === 'event')?.layers.find((layer) => layer.widgetAnimation)?.src ?? null);
const duration = computed(() => {
    if (!activeSlide.value || (activeSlide.value.kind === 'widget' && manualWidgets.has(activeSlide.value.key))) {
        return 0;
    }

    return activeSlide.value.kind === 'scene' ? 5500 : 4500;
});
const progress = computed(() => duration.value ? Math.min(100, elapsed.value / duration.value * 100) : 0);
const sceneLabel = computed(() => (props.labels.motion_scene ?? ':current / :total')
    .replace(':current', String(activeIndex.value + 1))
    .replace(':total', String(slides.value.length)));

let tickTimer = null;
let openTimer = null;
let revealTimer = null;
let lastTick = 0;

const widgetProps = (widget) => widget.key === 'rsvp'
    ? { widget, labels: props.labels, preview: props.preview, csrf: props.csrf }
    : { widget, labels: props.labels };

const goTo = (index) => {
    if (index < 0 || index >= slides.value.length) return;

    activeIndex.value = index;
    elapsed.value = 0;
    lastTick = Date.now();
};

const open = () => {
    if (opened.value) return;

    opened.value = true;
    emit('opened');
    revealTimer = window.setTimeout(() => {
        revealed.value = true;
        lastTick = Date.now();
    }, reducedMotion.value ? 0 : 850);
};

const tick = () => {
    const now = Date.now();
    const delta = now - lastTick;
    lastTick = now;

    if (!revealed.value || paused.value || document.hidden || !duration.value) return;

    elapsed.value += delta;

    if (elapsed.value >= duration.value) {
        if (activeIndex.value < slides.value.length - 1) {
            goTo(activeIndex.value + 1);
        } else {
            elapsed.value = duration.value;
            paused.value = true;
        }
    }
};

watch(slides, () => {
    if (activeIndex.value >= slides.value.length) goTo(Math.max(0, slides.value.length - 1));
});

onMounted(() => {
    reducedMotion.value = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    paused.value = reducedMotion.value;
    lastTick = Date.now();
    tickTimer = window.setInterval(tick, 80);

    if (props.autoOpen && (props.preview || !props.gate.enabled)) {
        openTimer = window.setTimeout(open, reducedMotion.value ? 0 : 1300);
    }
});

onBeforeUnmount(() => {
    window.clearInterval(tickTimer);
    window.clearTimeout(openTimer);
    window.clearTimeout(revealTimer);
});
</script>

<template>
    <div class="nkc-motion-shell" :class="{ 'has-widget-foreground': widgetForeground }">
        <div class="nkc-motion-frame">
            <div
                v-for="(slide, index) in slides"
                v-show="activeIndex === index"
                :key="`${slide.kind}-${slide.key}`"
                class="nkc-motion-slide"
                :class="{ 'is-active': revealed && activeIndex === index, 'nkc-motion-slide-widget': slide.kind === 'widget' }"
                :data-widget="slide.kind === 'widget' ? slide.key : null"
                :aria-hidden="activeIndex !== index"
                :inert="activeIndex !== index"
            >
                <CardScene
                    v-if="slide.kind === 'scene'"
                    :scene="slide.data"
                    :content="content"
                    :photos="photos"
                    :preview="preview"
                    :first="index === 0"
                />
                <component v-else :is="WIDGETS[slide.key]" v-bind="widgetProps(slide.data)" />
            </div>

            <img
                v-if="widgetForeground && activeSlide?.kind === 'widget'"
                class="nkc-motion-foreground"
                :src="widgetForeground"
                alt=""
                aria-hidden="true"
            />

            <img
                v-if="widgetAnimation && activeSlide?.kind === 'widget' && !reducedMotion"
                class="nkc-motion-butterflies"
                :src="widgetAnimation"
                alt=""
                aria-hidden="true"
            />

            <button
                v-if="!revealed"
                type="button"
                class="nkc-motion-envelope"
                :class="{ 'is-opening': opened, 'has-texture': gate.texture }"
                :style="gate.texture ? { '--nkc-gate-texture': `url('${gate.texture}')` } : null"
                :aria-label="gate.label ?? labels.open"
                :disabled="opened"
                @click="open"
            >
                <span class="nkc-motion-seal" :class="{ 'has-artwork': gate.seal }" aria-hidden="true">
                    <img v-if="gate.seal" class="nkc-motion-seal-art" :src="gate.seal" alt="" />
                    <span class="nkc-motion-seal-initials">{{ gate.initials ?? '✦' }}</span>
                </span>
                <span class="nkc-motion-open-label">{{ gate.label ?? labels.open }}</span>
            </button>
        </div>

        <div v-if="revealed" class="nkc-motion-controls">
            <div class="nkc-motion-progress" aria-hidden="true">
                <span
                    v-for="(slide, index) in slides"
                    :key="`${slide.kind}-${slide.key}`"
                    class="nkc-motion-progress-track"
                >
                    <span :style="{ width: `${index < activeIndex ? 100 : index === activeIndex ? progress : 0}%` }" />
                </span>
            </div>
            <div class="nkc-motion-actions">
                <button type="button" :disabled="activeIndex === 0" :aria-label="labels.motion_previous" @click="goTo(activeIndex - 1)">‹</button>
                <span aria-live="polite">{{ sceneLabel }}</span>
                <button
                    v-if="duration"
                    type="button"
                    :aria-label="paused ? labels.motion_play : labels.motion_pause"
                    @click="paused = !paused"
                >{{ paused ? '▶' : 'Ⅱ' }}</button>
                <slot name="music" />
                <button type="button" :disabled="activeIndex === slides.length - 1" :aria-label="labels.motion_next" @click="goTo(activeIndex + 1)">›</button>
            </div>
        </div>
    </div>
</template>
