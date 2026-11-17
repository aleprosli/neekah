<script setup>
/**
 * The bank picker on the order confirmation page (payments/checkout.blade.php).
 * Banks come from the gateway's live list, grouped by kind of account; the
 * choice is posted as payment_method + bank_code by the form around it.
 */
import { computed, ref } from 'vue';

const props = defineProps({
    groups: { type: Array, required: true },
    payLabel: { type: String, required: true },
    oldMethod: { type: String, default: '' },
    oldCode: { type: String, default: '' },
});

const methodKeys = { 'Online Banking': 'online_banking', 'Corporate Banking': 'corporate_banking' };

const initialGroup = props.groups.find((group) => group.method === props.oldMethod) ?? props.groups[0];
const method = ref(initialGroup?.method ?? '');
const code = ref(initialGroup?.channels.some((bank) => bank.code === props.oldCode) ? props.oldCode : '');
const search = ref('');

const current = computed(() => props.groups.find((group) => group.method === method.value) ?? { channels: [] });
const banks = computed(() => {
    const term = search.value.trim().toLowerCase();

    return term === '' ? current.value.channels : current.value.channels.filter((bank) => bank.name.toLowerCase().includes(term));
});
const chosen = computed(() => current.value.channels.find((bank) => bank.code === code.value) ?? null);

function switchTo(next) {
    if (next !== method.value) {
        method.value = next;
        code.value = '';
        search.value = '';
    }
}

function initials(name) {
    return name.split(/\s+/).filter(Boolean).slice(0, 2).map((word) => word[0]).join('').toUpperCase();
}
</script>

<template>
    <div class="flex flex-col gap-4">
        <input type="hidden" name="payment_method" :value="method">
        <input type="hidden" name="bank_code" :value="code">

        <div v-if="groups.length > 1" class="grid grid-cols-2 gap-1 rounded-full bg-surface-muted p-1" role="tablist">
            <button
                v-for="group in groups"
                :key="group.method"
                type="button"
                role="tab"
                :aria-selected="group.method === method"
                class="rounded-full px-3 py-2 text-sm font-semibold transition"
                :class="group.method === method ? 'bg-surface-raised text-ink shadow-sm' : 'text-ink-muted hover:text-ink'"
                @click="switchTo(group.method)"
            >
                {{ $t('payment_checkout.' + (methodKeys[group.method] ?? 'online_banking')) }}
            </button>
        </div>

        <label v-if="current.channels.length > 8" class="relative block">
            <span class="sr-only">{{ $t('payment_checkout.search') }}</span>
            <svg class="pointer-events-none absolute top-1/2 left-3.5 size-4 -translate-y-1/2 text-ink-muted" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="11" cy="11" r="7" /><path d="M20 20l-3.5-3.5" /></svg>
            <input
                v-model="search"
                type="search"
                :placeholder="$t('payment_checkout.search')"
                class="w-full rounded-full border border-line bg-surface-raised py-2.5 pr-4 pl-10 text-sm outline-none focus:border-brand-400 focus:ring-2 focus:ring-brand-100"
            >
        </label>

        <div class="grid max-h-[26rem] min-w-0 grid-cols-1 gap-2 overflow-y-auto p-0.5 sm:grid-cols-2" role="radiogroup">
            <label
                v-for="bank in banks"
                :key="bank.code"
                class="relative flex min-w-0 cursor-pointer items-center gap-3 rounded-2xl border bg-surface-raised px-3.5 py-3 transition"
                :class="bank.code === code ? 'border-brand-500 ring-2 ring-brand-100' : 'border-line hover:border-brand-300'"
            >
                <input v-model="code" type="radio" class="sr-only" name="bank_choice" :value="bank.code">
                <span class="flex size-10 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-white ring-1 ring-line">
                    <img v-if="bank.logo" :src="bank.logo" :alt="''" class="size-full object-contain p-1" loading="lazy">
                    <span v-else class="text-xs font-bold text-ink-muted">{{ initials(bank.name) }}</span>
                </span>
                <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ bank.name }}</span>
                <svg v-if="bank.code === code" class="size-5 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" :aria-label="$t('payment_checkout.selected')"><path d="M5 12.5l4.5 4.5L19 7.5" /></svg>
            </label>
            <p v-if="banks.length === 0" class="col-span-full py-6 text-center text-sm text-ink-muted">{{ $t('payment_checkout.none') }}</p>
        </div>

        <button
            type="submit"
            :disabled="!chosen"
            class="inline-flex w-full items-center justify-center gap-2 rounded-full bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 disabled:cursor-not-allowed disabled:opacity-50"
        >
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2" /><path d="M8 10V7a4 4 0 0 1 8 0v3" /></svg>
            <span>{{ chosen ? payLabel : $t('payment_checkout.choose_bank') }}</span>
        </button>
    </div>
</template>
