<script setup>
/**
 * Boost tokens for a vendor: the balance, lifting one of their categories to
 * the top of the "Disyorkan" list (one token a day), what is running, the
 * history, and the packs on sale.
 */
import { computed, ref } from 'vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    balance: { type: Number, required: true },
    maxDays: { type: Number, required: true },
    categories: { type: Array, required: true },
    running: { type: Array, default: () => [] },
    history: { type: Array, default: () => [] },
    packs: { type: Array, default: () => [] },
    isPro: { type: Boolean, default: false },
    proTokens: { type: Number, default: 0 },
    urls: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const categoryId = ref(props.categories[0]?.id ?? null);
const days = ref(Math.min(3, props.maxDays, Math.max(1, props.balance)));

const category = computed(() => props.categories.find((item) => item.id === Number(categoryId.value)));
const enough = computed(() => props.balance >= Number(days.value) && Number(days.value) > 0);

/** When the boost would end: after the one already running, or from this hour. */
const endsAt = computed(() => {
    const start = category.value?.ends_at ? new Date(category.value.ends_at) : new Date(new Date().setMinutes(0, 0, 0));
    start.setDate(start.getDate() + Number(days.value || 0));

    return start.toLocaleString(document.documentElement.lang || 'ms', { day: 'numeric', month: 'short', year: 'numeric', hour: 'numeric', minute: '2-digit' });
});

const input = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none';
</script>

<template>
    <div class="flex min-w-0 flex-col gap-8">
        <div class="grid min-w-0 gap-3 sm:grid-cols-3">
            <UiStatCard :label="$t('boost.balance')" :value="String(balance)" :hint="$t('boost.one_token')" />
            <UiStatCard :label="$t('boost.running')" :value="String(running.length)" />
            <UiStatCard :label="$t('boost.pro_monthly')" :value="isPro ? String(proTokens) : '—'" :hint="isPro ? $t('boost.pro_monthly_hint') : $t('boost.pro_monthly_free')" />
        </div>

        <!-- Lift a category. -->
        <form :action="urls.boost" method="POST" class="flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <input type="hidden" name="_token" :value="csrf">
            <div>
                <h2 class="font-semibold">{{ $t('boost.start_title') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('boost.start_hint') }}</p>
            </div>

            <div class="grid min-w-0 gap-4 sm:grid-cols-[minmax(0,1fr)_10rem]">
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('boost.category') }}</span>
                    <select v-model="categoryId" name="category_id" required :class="['nk-select pr-10', input]">
                        <option v-for="option in categories" :key="option.id" :value="option.id">{{ option.name }}</option>
                    </select>
                    <span v-if="errors.category_id" class="text-xs text-brand-700">{{ errors.category_id }}</span>
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('boost.days') }}</span>
                    <input v-model.number="days" type="number" name="days" min="1" :max="maxDays" required :class="input">
                </label>
            </div>

            <p class="text-sm" :class="enough ? 'text-ink-muted' : 'text-brand-700'">
                <template v-if="enough">{{ $t(category?.ends_at ? 'boost.preview_extend' : 'boost.preview', { count: days, date: endsAt }) }}</template>
                <template v-else>{{ $t('boost.not_enough', { count: balance }) }}</template>
            </p>
            <span v-if="errors.days || errors.tokens" class="text-xs text-brand-700">{{ errors.days || errors.tokens }}</span>

            <button type="submit" :disabled="!enough" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50 sm:self-start">
                {{ $t('boost.start', { count: days }) }}
            </button>
        </form>

        <!-- What is lifted now. -->
        <section v-if="running.length" class="flex min-w-0 flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">{{ $t('boost.running') }}</h2>
            <ul class="flex flex-col gap-2">
                <li v-for="boost in running" :key="boost.id" class="flex min-w-0 flex-wrap items-center justify-between gap-2 rounded-xl border border-line bg-surface-raised px-4 py-3">
                    <a :href="boost.url" target="_blank" rel="noopener" class="min-w-0 truncate text-sm font-medium hover:text-brand-700">🚀 {{ boost.category }}</a>
                    <span class="text-xs text-ink-muted">{{ $t('boost.until', { date: boost.ends }) }}</span>
                </li>
            </ul>
        </section>

        <!-- Buying more. -->
        <section class="flex min-w-0 flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">{{ $t('boost.buy_title') }}</h2>
            <div v-if="packs.length" class="grid min-w-0 gap-3 sm:grid-cols-2">
                <form v-for="pack in packs" :key="pack.id" :action="urls.checkout" method="POST" class="flex min-w-0 items-center justify-between gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="pack" :value="pack.id">
                    <div class="min-w-0">
                        <p class="font-semibold">{{ $t('boost.pack_tokens', { count: pack.tokens }) }}</p>
                        <p class="text-sm text-ink-muted">RM{{ Number(pack.price).toFixed(2) }}</p>
                    </div>
                    <button type="submit" class="shrink-0 rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('boost.buy') }}</button>
                </form>
            </div>
            <p v-else class="rounded-2xl bg-surface-muted p-4 text-sm text-ink-muted">{{ $t('boost.buy_closed') }}</p>
            <span v-if="errors.pack" class="text-xs text-brand-700">{{ errors.pack }}</span>
            <p v-if="!isPro" class="text-sm text-ink-muted">{{ $t('boost.pro_pitch', { count: proTokens }) }} <a :href="urls.pro" class="font-medium text-brand-700 underline underline-offset-4">{{ $t('boost.see_pro') }}</a></p>
        </section>

        <!-- Where the tokens came from and went. -->
        <section class="flex min-w-0 flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">{{ $t('boost.history') }}</h2>
            <p v-if="!history.length" class="text-sm text-ink-muted">{{ $t('boost.history_empty') }}</p>
            <ul v-else class="flex flex-col divide-y divide-line rounded-2xl border border-line bg-surface-raised">
                <li v-for="entry in history" :key="entry.id" class="flex min-w-0 items-center justify-between gap-3 px-4 py-3 text-sm">
                    <div class="min-w-0">
                        <p class="font-medium">{{ entry.reason }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ entry.date }}<span v-if="entry.note"> · {{ entry.note }}</span></p>
                    </div>
                    <span :class="['shrink-0 font-semibold', entry.change > 0 ? 'text-emerald-700' : 'text-ink-muted']">{{ entry.change > 0 ? '+' : '' }}{{ entry.change }}</span>
                </li>
            </ul>
        </section>
    </div>
</template>
