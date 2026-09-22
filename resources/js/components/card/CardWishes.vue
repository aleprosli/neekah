<script setup>
/**
 * The wishes the couple has approved. Guests write them with their RSVP, so there
 * is no write endpoint here for a stranger to find.
 */
import CardSection from './CardSection.vue';

defineProps({
    widget: { type: Object, required: true },
    labels: { type: Object, default: () => ({}) },
});
</script>

<template>
    <CardSection :heading="widget.heading">
        <ul v-if="widget.items.length" class="nkc-wishes">
            <li v-for="(wish, index) in widget.items" :key="index">
                <strong>{{ wish.name }}</strong>
                <p>{{ wish.message }}</p>
            </li>
        </ul>
        <p v-else class="nkc-note">{{ labels.wishes_empty }}</p>
        <p class="nkc-note">{{ widget.note }}</p>
    </CardSection>
</template>
