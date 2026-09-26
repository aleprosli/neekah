<script setup>
/** Everything an admin weighs before approving, suspending or re-ranking. */
import { computed, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiSelect from '../ui/UiSelect.vue';

const props = defineProps({
    vendor: { type: Object, required: true },
    facts: { type: Array, required: true },
    packages: { type: Array, required: true },
    portfolio: { type: Array, required: true },
    statusActions: { type: Array, required: true },
    tiers: { type: Array, required: true },
    boost: { type: Object, default: null },
    plan: { type: Object, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const tier = ref(props.vendor.tier);
const proPlan = ref(props.plan?.plans[0]?.value ?? 'monthly');
const proAmount = ref('0');
const chosenPlan = computed(() => props.plan?.plans.find((option) => option.value === proPlan.value));
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
        <div class="flex min-w-0 flex-col gap-6">
            <dl class="grid gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2 [&>div]:min-w-0">
                <div v-for="fact in facts" :key="fact.label" :class="fact.wide ? 'sm:col-span-2' : ''">
                    <dt class="text-ink-muted">{{ fact.label }}</dt>
                    <dd :class="fact.wide ? 'leading-relaxed' : 'font-semibold'">{{ fact.value }}</dd>
                    <dd v-if="fact.detail" class="break-words text-ink-muted">{{ fact.detail }}</dd>
                </div>
            </dl>

            <section class="rounded-2xl border border-line p-5">
                <h2 class="font-semibold">Pakej ({{ packages.length }})</h2>
                <p v-if="!packages.length" class="mt-2 text-sm text-ink-muted">{{ $t('admin_vendor.vendor_belum_menambah_pakej') }}</p>
                <ul v-else class="mt-3 divide-y divide-line text-sm">
                    <li v-for="item in packages" :key="item.name" class="flex items-center justify-between gap-3 py-2">
                        <span class="min-w-0">{{ item.name }} <span class="text-ink-muted">· {{ item.duration }}</span></span>
                        <span class="shrink-0 font-medium">{{ item.price }}</span>
                    </li>
                </ul>
            </section>

            <section class="rounded-2xl border border-line p-5">
                <h2 class="font-semibold">Portfolio ({{ portfolio.length }})</h2>
                <p v-if="!portfolio.length" class="mt-2 text-sm text-ink-muted">{{ $t('admin_vendor.belum_ada_gambar_portfolio') }}</p>
                <ul v-else class="mt-3 grid grid-cols-3 gap-2 sm:grid-cols-5">
                    <li v-for="photo in portfolio" :key="photo.url">
                        <img :src="photo.thumbnail || photo.url" alt="" loading="lazy" class="aspect-square w-full rounded-xl object-cover">
                    </li>
                </ul>
            </section>
        </div>

        <aside class="flex min-w-0 flex-col gap-4">
            <!-- Basic or Pro, and giving or ending Pro by hand. -->
            <section v-if="plan" :class="['flex flex-col gap-4 rounded-2xl border p-5', plan.isPro ? 'border-gold-300 bg-gold-300/10' : 'border-line bg-surface-raised']">
                <div class="flex items-center justify-between gap-2">
                    <h2 class="text-sm font-semibold">{{ $t('admin_vendor.plan_title') }}</h2>
                    <span :class="['rounded-full px-2.5 py-1 text-xs font-bold tracking-wide uppercase', plan.isPro ? 'bg-ink text-surface' : 'bg-surface-muted text-ink-muted']">{{ plan.isPro ? 'Pro' : 'Basic' }}</span>
                </div>
                <p class="-mt-2 text-sm text-ink-muted">
                    {{ plan.isPro ? $t('admin_vendor.plan_pro_until', { date: plan.until }) : plan.expired ? $t('admin_vendor.plan_expired', { date: plan.until }) : $t('admin_vendor.plan_basic') }}
                </p>

                <form :action="plan.storeUrl" method="POST" class="flex flex-col gap-3 border-t border-line/70 pt-4">
                    <input type="hidden" name="_token" :value="csrf">
                    <div class="grid grid-cols-2 gap-1 rounded-xl bg-surface-muted p-1">
                        <label v-for="option in plan.plans" :key="option.value" class="cursor-pointer">
                            <input v-model="proPlan" type="radio" name="plan" :value="option.value" class="peer sr-only">
                            <span class="block rounded-lg px-3 py-2 text-center text-sm font-medium text-ink-muted transition peer-checked:bg-surface-raised peer-checked:text-ink peer-checked:shadow-sm">{{ option.label }}</span>
                        </label>
                    </div>
                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_vendor.plan_amount') }}</span>
                        <input v-model="proAmount" type="number" name="amount" min="0" step="0.01" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <span class="text-xs text-ink-muted">{{ $t('admin_vendor.plan_amount_hint', { price: chosenPlan ? `RM${chosenPlan.price}` : '' }) }}</span>
                        <span v-if="errors.amount || errors.plan" class="text-xs text-brand-700">{{ errors.amount || errors.plan }}</span>
                    </label>
                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_vendor.plan_note') }}</span>
                        <input type="text" name="note" maxlength="255" :placeholder="$t('admin_vendor.plan_note_placeholder')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    </label>
                    <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ plan.isPro ? $t('admin_vendor.plan_extend') : $t('admin_vendor.plan_upgrade') }}</button>
                </form>

                <UiConfirm
                    v-if="plan.isPro"
                    :action="plan.endUrl"
                    method="DELETE"
                    tone="danger"
                    :title="$t('admin_vendor.plan_end_title')"
                    :message="$t('admin_vendor.plan_end_message')"
                    :confirm-label="$t('admin_vendor.plan_end')"
                    trigger-class="self-center text-xs font-medium text-ink-muted underline underline-offset-4 hover:text-brand-700"
                    :csrf="csrf"
                >{{ $t('admin_vendor.plan_end') }}</UiConfirm>

                <details v-if="plan.history.length" class="border-t border-line/70 pt-3 text-sm">
                    <summary class="cursor-pointer text-xs font-semibold text-ink-muted">{{ $t('admin_vendor.plan_history', { count: plan.history.length }) }}</summary>
                    <ul class="mt-2 flex flex-col gap-2">
                        <li v-for="item in plan.history" :key="item.id" class="rounded-xl bg-surface-raised px-3 py-2 text-xs">
                            <p class="flex justify-between gap-2 font-medium"><span>{{ item.plan }} · {{ item.amount }}</span><span class="text-ink-muted">{{ item.status }}</span></p>
                            <p class="text-ink-muted">{{ item.period }} · {{ item.source }}<span v-if="item.note"> · {{ item.note }}</span></p>
                        </li>
                    </ul>
                </details>
            </section>

            <div class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="text-sm font-semibold">Status: {{ vendor.status }}</h2>
                <!-- Every one of these emails the vendor and changes what the
                     marketplace shows, so none of them fires on one tap. -->
                <div class="flex flex-wrap gap-2">
                    <UiConfirm
                        v-for="option in statusActions"
                        :key="option.value"
                        :action="option.url"
                        :fields="{ status: option.value }"
                        :tone="option.tone || 'brand'"
                        :title="option.confirm_title"
                        :message="option.confirm_message"
                        :confirm-label="option.label"
                        :trigger-class="[
                            'rounded-full px-4 py-2 text-sm font-medium transition',
                            option.primary ? 'bg-brand-600 text-white hover:bg-brand-700' : 'border border-line hover:border-brand-400',
                        ].join(' ')"
                        :csrf="csrf"
                    >{{ option.label }}</UiConfirm>
                </div>
            </div>

            <form :action="vendor.tier_url" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">

                <h2 class="text-sm font-semibold">{{ $t('admin_vendor.ranking') }}</h2>

                <UiSelect v-model="tier" :label="$t('admin_vendor.tahap_vendor')" name="tier" :options="tiers" :error="errors.tier" required />

                <p class="text-xs text-ink-muted">{{ $t('admin_vendor.response_rate_dikira_dari_enquiry') }}</p>

                <label class="flex items-start gap-2 text-sm">
                    <input type="hidden" name="tier_locked" value="0">
                    <input type="checkbox" name="tier_locked" value="1" class="mt-0.5 accent-brand-600" :checked="vendor.tier_locked">
                    <span>{{ $t('admin_vendor.kunci_tahap_ini') }}<span class="block text-xs text-ink-muted">{{ $t('admin_vendor.pengiraan_automatik_tidak_akan_menaik') }}</span>
                    </span>
                </label>

                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('admin_vendor.simpan_ranking') }}</button>

                <p class="text-xs text-ink-muted">{{ $t('admin_vendor.vendor_score_rating_30_booking') }}</p>
            </form>

            <!-- Boost tokens: give some (a promotion) or take some back. -->
            <form v-if="boost" :action="boost.url" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <input type="hidden" name="_token" :value="csrf">
                <div class="flex items-baseline justify-between gap-2">
                    <h2 class="text-sm font-semibold">{{ $t('admin_vendor.boost_title') }}</h2>
                    <span class="text-sm font-semibold">{{ $t('admin_vendor.boost_balance', { count: boost.balance }) }}</span>
                </div>
                <ul v-if="boost.running.length" class="flex flex-col gap-1 text-xs text-ink-muted">
                    <li v-for="item in boost.running" :key="item.id">🚀 {{ item.category }} · {{ item.ends }}</li>
                </ul>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('admin_vendor.boost_change') }}</span>
                    <input type="number" name="change" required :min="-boost.max" :max="boost.max" step="1" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <span class="text-xs text-ink-muted">{{ $t('admin_vendor.boost_change_hint') }}</span>
                    <span v-if="errors.change || errors.tokens" class="text-xs text-brand-700">{{ errors.change || errors.tokens }}</span>
                </label>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('admin_vendor.boost_note') }}</span>
                    <input type="text" name="note" maxlength="255" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('admin_vendor.boost_save') }}</button>
            </form>
        </aside>
    </div>
</template>
