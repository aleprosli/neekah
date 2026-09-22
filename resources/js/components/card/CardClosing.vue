<script setup>
/**
 * The last word, signed by the couple.
 */
import CardSection from './CardSection.vue';

defineProps({
    widget: { type: Object, required: true },
});
</script>

<template>
    <CardSection :heading="null">
        <p v-if="widget.note" class="nkc-body nkc-closing">{{ widget.note }}</p>
        <p class="nkc-signature">{{ widget.signature }}</p>
    </CardSection>
</template>
