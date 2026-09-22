<script setup>
/**
 * Who to call. Both actions leave the card and open the phone's own apps.
 */
import CardSection from './CardSection.vue';

defineProps({
    widget: { type: Object, required: true },
    labels: { type: Object, default: () => ({}) },
});
</script>

<template>
    <CardSection :heading="widget.heading">
        <ul class="nkc-contacts">
            <li v-for="(contact, index) in widget.items" :key="index">
                <span class="nkc-contact-name">{{ contact.name }}</span>
                <span class="nkc-contact-phone">{{ contact.phone }}</span>
                <span class="nkc-actions">
                    <a class="nkc-btn nkc-btn-ghost nkc-btn-small" :href="`tel:${contact.phone.replace(/\s+/g, '')}`">{{ labels.contacts_call }}</a>
                    <a v-if="contact.whatsapp" class="nkc-btn nkc-btn-ghost nkc-btn-small" :href="contact.whatsapp" target="_blank" rel="noopener">{{ labels.contacts_whatsapp }}</a>
                </span>
            </li>
        </ul>
    </CardSection>
</template>
