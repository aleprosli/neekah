<script setup>
/**
 * The photo grid at the top of a vendor page, and the lightbox behind it.
 *
 * The grid still shows five photos, because that is what the layout holds, but
 * every photo the vendor published is reachable: through "Tunjuk semua", by
 * tapping any tile, and by swiping inside the lightbox.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps({
    photos: { type: Array, required: true },
    vendorName: { type: String, required: true },
    tone: { type: String, default: '' },
});

const HERO_COUNT = 5;
const SWIPE_THRESHOLD = 40;

const open = ref(false);
const index = ref(0);
const touchStartX = ref(null);

const hero = computed(() => props.photos.slice(0, HERO_COUNT));
const current = computed(() => props.photos[index.value] ?? null);
const hasMore = computed(() => props.photos.length > HERO_COUNT);

const show = (at) => {
    index.value = at;
    open.value = true;
};

const close = () => (open.value = false);
const next = () => (index.value = (index.value + 1) % props.photos.length);
const previous = () => (index.value = (index.value - 1 + props.photos.length) % props.photos.length);

const onKey = (event) => {
    if (event.key === 'Escape') close();
    if (event.key === 'ArrowRight') next();
    if (event.key === 'ArrowLeft') previous();
};

const onTouchStart = (event) => (touchStartX.value = event.changedTouches[0].clientX);

const onTouchEnd = (event) => {
    if (touchStartX.value === null) return;

    const travelled = event.changedTouches[0].clientX - touchStartX.value;
    touchStartX.value = null;

    if (Math.abs(travelled) < SWIPE_THRESHOLD) return;

    travelled < 0 ? next() : previous();
};

// The page behind must not scroll while the lightbox is over it.
watch(open, (isOpen) => {
    document.body.style.overflow = isOpen ? 'hidden' : '';
    isOpen ? window.addEventListener('keydown', onKey) : window.removeEventListener('keydown', onKey);
});

onBeforeUnmount(() => {
    document.body.style.overflow = '';
    window.removeEventListener('keydown', onKey);
});
</script>

<template>
    <div class="relative">
        <div class="grid h-72 grid-cols-4 grid-rows-2 gap-2 sm:overflow-hidden sm:rounded-2xl md:h-[420px]">
            <template v-if="photos.length">
                <button
                    v-for="(photo, at) in hero"
                    :key="photo.id"
                    type="button"
                    :class="[
                        'group relative overflow-hidden bg-surface-muted',
                        at === 0 ? 'col-span-4 row-span-2 md:col-span-2' : 'hidden md:block',
                    ]"
                    @click="show(at)"
                >
                    <!-- Vendors upload portrait photos straight off a phone, and
                         cropping one into this landscape box hid half of it. The
                         photo is shown whole, over a blurred copy of itself so the
                         cell is still filled. A landscape photo is so close to the
                         cell's own shape that it covers the blur entirely. -->
                    <img
                        :src="photo.thumbnail || photo.url"
                        alt=""
                        aria-hidden="true"
                        loading="lazy"
                        decoding="async"
                        class="absolute inset-0 size-full scale-110 object-cover blur-xl"
                    >
                    <img
                        :src="at === 0 ? photo.url : photo.thumbnail"
                        :alt="photo.caption || vendorName"
                        :fetchpriority="at === 0 ? 'high' : undefined"
                        :loading="at === 0 ? undefined : 'lazy'"
                        decoding="async"
                        class="relative size-full object-contain transition duration-300 group-hover:scale-[1.03]"
                    >
                </button>
            </template>

            <template v-else>
                <div :class="['col-span-4 row-span-2 bg-linear-to-br md:col-span-2', tone]"></div>
                <div :class="['hidden bg-linear-to-br opacity-80 md:block', tone]"></div>
                <div :class="['hidden bg-linear-to-tr opacity-60 md:block', tone]"></div>
                <div :class="['hidden bg-linear-to-tl opacity-70 md:block', tone]"></div>
                <div :class="['hidden bg-linear-to-bl opacity-50 md:block', tone]"></div>
            </template>
        </div>

        <button
            v-if="photos.length"
            type="button"
            class="absolute right-4 bottom-4 rounded-full border border-line bg-surface/95 px-4 py-2 text-sm font-semibold shadow-lg backdrop-blur transition hover:bg-surface sm:right-5 sm:bottom-5"
            @click="show(0)"
        >
            <span aria-hidden="true">🖼️</span>
            {{ hasMore ? `Tunjuk semua ${photos.length} gambar` : `Lihat ${photos.length} gambar` }}
        </button>

        <Teleport to="body">
            <div
                v-if="open"
                class="fixed inset-0 z-50 flex flex-col bg-black/95"
                role="dialog"
                aria-modal="true"
                :aria-label="`Galeri ${vendorName}`"
                @touchstart.passive="onTouchStart"
                @touchend.passive="onTouchEnd"
            >
                <div class="flex items-center justify-between px-4 py-3 text-white sm:px-6">
                    <p class="text-sm font-medium">{{ index + 1 }} / {{ photos.length }}</p>
                    <button type="button" class="rounded-full px-3 py-1.5 text-2xl leading-none transition hover:bg-white/10" :aria-label="$t('common.close')" @click="close">&times;</button>
                </div>

                <div class="relative flex min-h-0 flex-1 items-center justify-center px-2 sm:px-6">
                    <button
                        type="button"
                        class="absolute left-2 hidden size-11 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20 sm:flex"
                        :aria-label="$t('gallery.previous_photo')"
                        @click="previous"
                    >‹</button>

                    <img :src="current.url" :alt="current.caption || vendorName" class="max-h-full max-w-full rounded-xl object-contain">

                    <button
                        type="button"
                        class="absolute right-2 hidden size-11 items-center justify-center rounded-full bg-white/10 text-2xl text-white transition hover:bg-white/20 sm:flex"
                        :aria-label="$t('gallery.next_photo')"
                        @click="next"
                    >›</button>
                </div>

                <div class="shrink-0 px-4 pt-3 pb-5 sm:px-6">
                    <p v-if="current.caption" class="mb-3 text-center text-sm text-white/80">{{ current.caption }}</p>
                    <p class="mb-3 text-center text-xs text-white/50 sm:hidden">{{ $t('gallery.swipe_hint') }}</p>

                    <div class="no-scrollbar flex gap-2 overflow-x-auto">
                        <button
                            v-for="(photo, at) in photos"
                            :key="photo.id"
                            type="button"
                            :class="[
                                'size-14 shrink-0 overflow-hidden rounded-lg transition',
                                at === index ? 'ring-2 ring-white' : 'opacity-50 hover:opacity-100',
                            ]"
                            :aria-label="`Gambar ${at + 1}`"
                            @click="index = at"
                        >
                            <img :src="photo.thumbnail" alt="" loading="lazy" class="size-full object-cover">
                        </button>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>
