<script setup>
/** The vendor's package list: what a couple picks from when they book. */
import UiConfirm from '../ui/UiConfirm.vue';

defineProps({
    packages: { type: Array, required: true },
    createUrl: { type: String, required: true },
    csrf: { type: String, required: true },
});
</script>

<template>
    <div v-if="!packages.length" class="flex flex-col items-center gap-3 rounded-2xl border border-dashed border-line px-6 py-16 text-center">
        <span class="text-4xl">📦</span>
        <h2 class="text-lg font-semibold">{{ $t('packages.belum_ada_pakej') }}</h2>
        <p class="max-w-sm text-sm text-ink-muted">{{ $t('packages.tambah_sekurang_kurangnya_satu_pakej') }}</p>
        <a :href="createUrl" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('packages.tambah_pakej_pertama') }}</a>
    </div>

    <ul v-else class="grid gap-4 md:grid-cols-2">
        <li v-for="item in packages" :key="item.id" class="flex flex-col overflow-hidden rounded-2xl border border-line bg-surface-raised">
            <img v-if="item.thumbnail" :src="item.thumbnail" alt="" loading="lazy" class="aspect-[4/3] w-full object-cover">

            <div class="flex flex-1 flex-col gap-3 p-5">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <h2 class="font-semibold">{{ item.name }}</h2>
                        <p class="text-sm text-ink-muted">{{ item.duration }}</p>
                    </div>
                    <div class="shrink-0 text-right">
                        <p class="font-semibold">{{ item.price }}</p>
                        <span v-if="!item.is_active" class="text-xs text-ink-muted">{{ $t('packages.tidak_aktif') }}</span>
                    </div>
                </div>

                <ul class="flex flex-col gap-1 text-sm text-ink-muted">
                    <li v-for="(feature, at) in item.features" :key="at" class="flex gap-2"><span class="text-brand-600">✓</span>{{ feature }}</li>
                </ul>

                <div class="mt-auto flex gap-2 border-t border-line pt-3 text-sm">
                    <a :href="item.edit_url" class="rounded-full border border-line px-4 py-1.5 font-medium transition hover:border-brand-400">{{ $t('packages.edit') }}</a>
                    <UiConfirm
                        :action="item.destroy_url"
                        method="DELETE"
                        tone="danger"
                        :title="$t('packages.padam_pakej_nama', { name: item.name })"
                        :message="$t('packages.pakej_ini_tidak_akan_dipaparkan')"
                        :confirm-label="$t('packages.padam_pakej')"
                        trigger-class="rounded-full px-4 py-1.5 font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink"
                        :csrf="csrf"
                    >{{ $t('packages.padam') }}</UiConfirm>
                </div>
            </div>
        </li>
    </ul>
</template>
