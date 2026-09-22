<script setup>
/**
 * Days, hours, minutes and seconds to the ceremony. It counts to the start of the
 * majlis, not to midnight, because "0 hari lagi" on the morning of the wedding is
 * the one day a guest is most likely to look.
 */
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CardSection from './CardSection.vue';

const props = defineProps({
    widget: { type: Object, required: true },
    labels: { type: Object, default: () => ({}) },
});

const remaining = ref(0);
let timer = null;

const tick = () => {
    const target = props.widget.target ? new Date(props.widget.target).getTime() : 0;

    remaining.value = Math.max(0, target - Date.now());
};

onMounted(() => {
    tick();
    timer = window.setInterval(tick, 1000);
});

onBeforeUnmount(() => timer && window.clearInterval(timer));

const cells = computed(() => {
    const seconds = Math.floor(remaining.value / 1000);

    return [
        { key: 'days', value: Math.floor(seconds / 86400), label: props.labels.countdown_days },
        { key: 'hours', value: Math.floor((seconds % 86400) / 3600), label: props.labels.countdown_hours },
        { key: 'minutes', value: Math.floor((seconds % 3600) / 60), label: props.labels.countdown_minutes },
        { key: 'seconds', value: seconds % 60, label: props.labels.countdown_seconds },
    ];
});
</script>

<template>
    <CardSection :heading="widget.heading">
        <div class="nkc-countdown" data-countdown>
            <div v-for="cell in cells" :key="cell.key" class="nkc-cd-cell" :data-unit="cell.key">
                <strong>{{ String(cell.value).padStart(2, '0') }}</strong>
                <span>{{ cell.label }}</span>
            </div>
        </div>
    </CardSection>
</template>
