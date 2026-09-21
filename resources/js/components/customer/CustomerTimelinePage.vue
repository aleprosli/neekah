<script setup>
/**
 * The running order of the wedding day. Each slot may name a vendor, and that
 * vendor sees only their own slots on their side of the platform.
 */
import UiConfirm from '../ui/UiConfirm.vue';
import UiEmptyState from '../ui/UiEmptyState.vue';

defineProps({
    items: { type: Array, required: true },
    vendors: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
</script>

<template>
    <p v-if="!vendors.length" class="mb-6 rounded-2xl border border-line bg-surface-muted p-4 text-sm text-ink-muted">{{ $t('timeline.tempah_vendor_dahulu_untuk_menugaskan') }}</p>

    <form :action="storeUrl" method="POST" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
        <input type="hidden" name="_token" :value="csrf">

        <div class="grid gap-3 sm:grid-cols-[7rem_7rem_1fr]">
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.mula') }}</span>
                <input type="time" name="starts_at" required class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.tamat') }}</span>
                <input type="time" name="ends_at" class="rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.aktiviti') }}</span>
                <input type="text" name="title" :placeholder="$t('timeline.contoh_akad_nikah')" required class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.vendor_terlibat') }}</span>
                <select name="vendor_id" class="nk-select rounded-xl border border-line bg-surface px-3 py-2.5 pr-9 text-sm focus:border-brand-400 focus:outline-none">
                    <option value="">{{ $t('timeline.tiada') }}</option>
                    <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">{{ vendor.icon }} {{ vendor.name }}</option>
                </select>
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.lokasi') }}</span>
                <input type="text" name="location" :placeholder="$t('timeline.rumah_pengantin_dewan')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.nota') }}</span>
                <input type="text" name="notes" :placeholder="$t('timeline.arahan_untuk_vendor')" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            </label>
        </div>

        <p v-for="message in Object.values(errors)" :key="message" class="text-xs text-brand-700">{{ message }}</p>

        <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('timeline.tambah_aktiviti') }}</button>
    </form>

    <section class="mt-8">
        <UiEmptyState
            v-if="!items.length"
            icon="🗓️"
            :title="$t('timeline.timeline_masih_kosong')"
            :message="$t('timeline.susun_perjalanan_hari_majlis_dari')"
        />

        <ol v-else class="relative flex flex-col gap-1 border-l-2 border-line pl-6 sm:pl-8">
            <li v-for="item in items" :key="item.id" class="relative pb-6 last:pb-0">
                <span class="absolute top-1.5 -left-[1.85rem] flex size-3 rounded-full bg-brand-600 ring-4 ring-surface sm:-left-[2.35rem]"></span>

                <div class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-start">
                    <div class="shrink-0 sm:w-32">
                        <p class="font-display text-lg font-semibold">{{ item.starts_at }}</p>
                        <p v-if="item.ends_at" class="text-xs text-ink-muted">hingga {{ item.ends_at }}</p>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ item.title }}</p>

                        <p class="flex flex-wrap items-center gap-x-2 text-sm text-ink-muted">
                            <a v-if="item.vendor" :href="item.vendor.url" class="flex items-center gap-1 hover:text-ink">
                                <img v-if="item.vendor.illustration" :src="item.vendor.illustration" alt="" class="size-5 object-contain mix-blend-multiply">
                                <span v-else aria-hidden="true">{{ item.vendor.icon }}</span>
                                {{ item.vendor.name }}
                            </a>
                            <span v-if="item.location">📍 {{ item.location }}</span>
                        </p>

                        <p v-if="item.notes" class="mt-1 text-sm text-ink-muted">{{ item.notes }}</p>
                    </div>

                    <UiConfirm
                        :action="item.destroy_url"
                        method="DELETE"
                        tone="danger"
                        :title="$t('timeline.padam_aktiviti_ini')"
                        :message="`${item.starts_at} · ${item.title}`"
                        confirm-:label="$t('timeline.padam')"
                        trigger-class="shrink-0 self-start text-xs font-medium text-ink-muted hover:text-brand-700"
                        :csrf="csrf"
                    >{{ $t('timeline.padam_2') }}</UiConfirm>
                </div>
            </li>
        </ol>
    </section>
</template>
