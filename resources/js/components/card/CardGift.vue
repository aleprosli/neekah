<script setup>
/**
 * Salam kaut: the accounts a guest may send a gift to, and the QR they scan.
 *
 * The account number can be copied with one tap, because the alternative is a
 * guest squinting at a phone screen and typing sixteen digits by hand.
 */
import { ref } from 'vue';
import CardSection from './CardSection.vue';

defineProps({
    widget: { type: Object, required: true },
    labels: { type: Object, default: () => ({}) },
});

const copied = ref(null);

const copy = async (number, index) => {
    try {
        await navigator.clipboard.writeText(number);
        copied.value = index;
        window.setTimeout(() => (copied.value = null), 2000);
    } catch {
        copied.value = null;
    }
};
</script>

<template>
    <CardSection :heading="widget.heading">
        <p v-if="widget.note" class="nkc-body">{{ widget.note }}</p>

        <ul class="nkc-accounts">
            <li v-for="(account, index) in widget.accounts" :key="index">
                <span class="nkc-account-bank">{{ account.bank }}</span>
                <span class="nkc-account-number">{{ account.number }}</span>
                <span v-if="account.holder" class="nkc-account-holder">{{ account.holder }}</span>
                <button type="button" class="nkc-btn nkc-btn-ghost nkc-btn-small" @click="copy(account.number, index)">
                    {{ copied === index ? labels.gift_copied : labels.gift_copy }}
                </button>
            </li>
        </ul>

        <img v-if="widget.qrUrl" class="nkc-qr" :src="widget.qrUrl" alt="QR" loading="lazy">
    </CardSection>
</template>
