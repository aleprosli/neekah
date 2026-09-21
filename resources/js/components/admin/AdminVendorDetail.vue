<script setup>
/** Everything an admin weighs before approving, suspending or re-ranking. */
import { ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiSelect from '../ui/UiSelect.vue';

const props = defineProps({
    vendor: { type: Object, required: true },
    facts: { type: Array, required: true },
    packages: { type: Array, required: true },
    portfolio: { type: Array, required: true },
    statusActions: { type: Array, required: true },
    tiers: { type: Array, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const tier = ref(props.vendor.tier);
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
        </aside>
    </div>
</template>
