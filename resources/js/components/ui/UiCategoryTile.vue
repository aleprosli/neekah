<script setup>
/** The vendor's category as a tile: its illustration over the vendor's tone. */
defineProps({
    illustration: { type: String, default: null },
    icon: { type: String, default: null },
    tone: { type: String, default: '' },
    size: { type: String, default: 'size-12' },
});
</script>

<template>
    <span :class="['flex shrink-0 items-center justify-center rounded-xl bg-linear-to-br p-1', size, tone]">
        <img v-if="illustration" :src="illustration" alt="" loading="lazy" class="size-full object-contain mix-blend-multiply">
        <span v-else aria-hidden="true">{{ icon }}</span>
    </span>
</template>
