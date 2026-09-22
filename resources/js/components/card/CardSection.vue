<script setup>
/**
 * The frame every widget section sits in: the design's inner paper, a script
 * heading and a hairline rule, so a section reads as part of the printed card and
 * not as a web panel bolted underneath it.
 */
defineProps({
    heading: { type: String, default: null },
    tone: { type: String, default: 'card' },
});
</script>

<template>
    <section class="nkc-section" :data-tone="tone">
        <div class="nkc-section-inner">
            <template v-if="heading">
                <h2 class="nkc-heading">{{ heading }}</h2>
                <span class="nkc-rule" aria-hidden="true" />
            </template>
            <slot />
        </div>
    </section>
</template>
