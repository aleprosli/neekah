<script setup>
/**
 * The physical cards.
 *
 * A tag carries /n/{uid} and nothing else, so a batch can be printed before anyone
 * has chosen an address, and a couple changing their address never bricks a card.
 * A card that has been tapped is out in the world, so it can be deactivated but not
 * deleted.
 */
import { ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    storeUrl: { type: String, required: true },
    maxBatch: { type: Number, required: true },
    stats: { type: Array, default: () => [] },
    sites: { type: Array, default: () => [] },
    cards: { type: Array, default: () => [] },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const batch = ref({ quantity: 10, label: '' });
const copied = ref(null);

const copy = async (card) => {
    try {
        await navigator.clipboard.writeText(card.url);
        copied.value = card.id;
        window.setTimeout(() => (copied.value = null), 2000);
    } catch {
        copied.value = null;
    }
};
</script>

<template>
    <div class="flex flex-col gap-8">
        <div class="grid gap-3 sm:grid-cols-3">
            <UiStatCard v-for="stat in stats" :key="stat.label" :label="stat.label" :value="stat.value" />
        </div>

        <form :action="storeUrl" method="POST" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <input type="hidden" name="_token" :value="csrf">
            <div>
                <h2 class="font-semibold">{{ $t('card_nfc.jana_kad') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('card_nfc.setiap_kad_dapat_uid_sendiri') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-[8rem_1fr]">
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('card_nfc.kuantiti') }}</span>
                    <input v-model="batch.quantity" type="number" name="quantity" min="1" :max="maxBatch" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('card_nfc.label_kelompok_pilihan') }}</span>
                    <input v-model="batch.label" type="text" name="label" :placeholder="$t('card_nfc.contoh_kelompok_pertama')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>

            <span v-if="errors.quantity" class="text-xs text-brand-700">{{ errors.quantity }}</span>

            <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('card_nfc.jana') }}</button>
        </form>

        <section class="flex flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">{{ $t('card_nfc.senarai_kad') }}</h2>
            <p v-if="!cards.length" class="text-sm text-ink-muted">{{ $t('card_nfc.belum_ada_kad') }}</p>

            <ul class="flex flex-col gap-3">
                <li v-for="card in cards" :key="card.id" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate font-mono text-sm font-semibold">{{ card.uid }}</p>
                            <p class="truncate text-xs text-ink-muted">
                                {{ card.label || '—' }} · {{ $t('card_nfc.tap_kali', { count: card.taps }) }}<span v-if="card.last_tapped"> · {{ card.last_tapped }}</span>
                            </p>
                        </div>
                        <span :class="['shrink-0 rounded-full px-3 py-1 text-xs font-medium', card.site ? 'bg-emerald-50 text-emerald-700' : 'bg-surface-muted text-ink-muted']">
                            {{ card.site || $t('card_nfc.belum_ditetapkan') }}
                        </span>
                        <button type="button" class="shrink-0 rounded-full border border-line px-4 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="copy(card)">
                            {{ copied === card.id ? $t('card_nfc.disalin') : $t('card_nfc.salin_pautan') }}
                        </button>
                        <UiConfirm
                            v-if="!card.taps"
                            :action="card.destroy_url"
                            method="DELETE"
                            tone="danger"
                            :title="$t('card_nfc.padam_kad', { uid: card.uid })"
                            :message="$t('card_nfc.kad_belum_pernah_ditap')"
                            :confirm-label="$t('card_nfc.padam')"
                            :csrf="csrf"
                            :label="$t('card_nfc.padam')"
                            trigger-class="shrink-0 rounded-full border border-line px-4 py-1.5 text-xs font-medium transition hover:border-red-300 hover:text-red-700"
                        />
                    </div>

                    <form :action="card.update_url" method="POST" class="flex flex-wrap items-end gap-3 border-t border-line pt-3">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="_method" value="PUT">

                        <label class="flex min-w-48 flex-1 flex-col gap-1.5">
                            <span class="text-xs font-medium">{{ $t('card_nfc.jemputan') }}</span>
                            <select :name="'wedding_site_id'" class="nk-select rounded-xl border border-line bg-surface py-2 pr-10 pl-4 text-sm focus:border-brand-400 focus:outline-none">
                                <option value="">{{ $t('card_nfc.belum_ditetapkan') }}</option>
                                <option v-for="site in sites" :key="site.id" :value="site.id" :selected="site.id === card.site_id">{{ site.label }}</option>
                            </select>
                        </label>

                        <label class="flex min-w-40 flex-1 flex-col gap-1.5">
                            <span class="text-xs font-medium">{{ $t('card_nfc.label') }}</span>
                            <input type="text" name="label" :value="card.label" class="rounded-xl border border-line bg-surface px-4 py-2 text-sm focus:border-brand-400 focus:outline-none">
                        </label>

                        <label class="flex items-center gap-2 pb-2 text-sm">
                            <input type="hidden" name="is_active" value="0">
                            <input type="checkbox" name="is_active" value="1" :checked="card.is_active" class="accent-brand-600">{{ $t('card_nfc.aktif') }}</label>

                        <button type="submit" class="rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('card_nfc.simpan') }}</button>
                    </form>
                </li>
            </ul>
        </section>
    </div>
</template>
