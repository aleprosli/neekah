<script setup>
/** A row of summary figures, used at the top of several admin pages. */
import UiStatCard from '../ui/UiStatCard.vue';

defineProps({
    stats: { type: Array, required: true },
    columns: { type: String, default: 'sm:grid-cols-2 xl:grid-cols-4' },
});
</script>

<template>
    <div :class="['grid gap-4', columns]">
        <UiStatCard v-for="stat in stats" :key="stat.label" v-bind="stat" />
    </div>
</template>
