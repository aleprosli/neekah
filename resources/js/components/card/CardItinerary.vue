<script setup>
/**
 * The tentatif: what happens at what time, as a printed order of the day.
 */
import CardSection from './CardSection.vue';

defineProps({
    widget: { type: Object, required: true },
});
</script>

<template>
    <CardSection :heading="widget.heading">
        <ul class="nkc-timeline">
            <li v-for="(row, index) in widget.rows" :key="index">
                <span class="nkc-time">{{ row.time }}</span>
                <span class="nkc-timeline-label">{{ row.label }}</span>
            </li>
        </ul>
    </CardSection>
</template>
