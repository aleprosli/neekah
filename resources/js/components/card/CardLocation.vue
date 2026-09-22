<script setup>
/**
 * Where the majlis is, with the two things a guest actually taps: directions and
 * a calendar entry. Both leave the card, so neither is hidden behind a dialog.
 */
import CardSection from './CardSection.vue';

defineProps({
    widget: { type: Object, required: true },
    labels: { type: Object, default: () => ({}) },
});
</script>

<template>
    <CardSection :heading="widget.heading">
        <p v-if="widget.venue" class="nkc-venue">{{ widget.venue }}</p>
        <p v-if="widget.address" class="nkc-address">{{ widget.address }}</p>

        <div class="nkc-actions">
            <a v-if="widget.mapUrl" class="nkc-btn" :href="widget.mapUrl" target="_blank" rel="noopener">
                <svg class="nkc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 21s-7-6.2-7-11a7 7 0 1 1 14 0c0 4.8-7 11-7 11Z" /><circle cx="12" cy="10" r="2.5" />
                </svg>
                {{ labels.location_open_map }}
            </a>
            <a v-if="widget.calendarUrl" class="nkc-btn nkc-btn-ghost" :href="widget.calendarUrl">
                <svg class="nkc-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="5" width="18" height="16" rx="2" /><path d="M8 3v4M16 3v4M3 11h18" />
                </svg>
                {{ labels.location_calendar }}
            </a>
        </div>
    </CardSection>
</template>
