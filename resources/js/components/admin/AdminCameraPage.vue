<script setup>
/**
 * Kamera Majlis for the admin: the numbers, what guests reported, a purchase
 * paid outside Herepay, and every album (taking one down sits on its row).
 */
import { ref } from 'vue';
import DataTable from '../ui/DataTable.vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiStatCard from '../ui/UiStatCard.vue';

const props = defineProps({
    stats: { type: Array, default: () => [] },
    table: { type: Object, required: true },
    reported: { type: Array, default: () => [] },
    tiers: { type: Array, required: true },
    grantUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const grantOpen = ref(Object.keys(props.errors).length > 0);
</script>

<template>
    <div class="flex min-w-0 flex-col gap-8">
        <div class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <UiStatCard v-for="stat in stats" :key="stat.label" :label="stat.label" :value="stat.value" />
        </div>

        <!-- What guests reported, oldest first. -->
        <section v-if="reported.length" class="flex min-w-0 flex-col gap-3 rounded-2xl border border-amber-200 bg-amber-50/60 p-4 sm:p-6">
            <div>
                <h2 class="font-semibold">{{ $t('admin_camera.reported_title') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('admin_camera.reported_hint') }}</p>
            </div>
            <ul class="grid min-w-0 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                <li v-for="item in reported" :key="item.id" class="flex min-w-0 gap-3 rounded-xl border border-line bg-surface-raised p-3">
                    <a :href="item.url" target="_blank" rel="noopener" class="shrink-0">
                        <img v-if="item.type === 'photo'" :src="item.thumb" alt="" class="size-20 rounded-lg object-cover">
                        <span v-else class="flex size-20 items-center justify-center rounded-lg bg-ink/80 text-2xl text-white" aria-hidden="true">▶</span>
                    </a>
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <p class="truncate text-sm font-medium">{{ item.wedding }}</p>
                        <p class="text-xs break-words text-ink-muted">{{ item.reason || $t('admin_camera.no_reason') }}</p>
                        <p class="truncate text-xs text-ink-muted">{{ item.by ? $t('admin_camera.reported_by', { name: item.by }) + ' · ' : '' }}{{ item.reported }}</p>
                        <div class="mt-auto flex flex-wrap gap-2 pt-1">
                            <UiConfirm
                                :action="item.destroy_url"
                                method="DELETE"
                                tone="danger"
                                :title="$t('admin_camera.delete_title')"
                                :message="$t('admin_camera.delete_message')"
                                :confirm-label="$t('admin_camera.delete')"
                                :csrf="csrf"
                            >{{ $t('admin_camera.delete') }}</UiConfirm>
                            <form :action="item.dismiss_url" method="POST">
                                <input type="hidden" name="_token" :value="csrf">
                                <button type="submit" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400 hover:text-brand-700">{{ $t('admin_camera.dismiss') }}</button>
                            </form>
                        </div>
                    </div>
                </li>
            </ul>
        </section>

        <!-- A purchase paid outside Herepay. -->
        <details :open="grantOpen" class="min-w-0 rounded-2xl border border-line bg-surface-muted/40 p-4 sm:p-6">
            <summary class="cursor-pointer text-sm font-semibold">{{ $t('admin_camera.grant_title') }}</summary>
            <p class="mt-2 text-sm text-ink-muted">{{ $t('admin_camera.grant_hint') }}</p>

            <form :action="grantUrl" method="POST" class="mt-4 flex flex-col gap-4">
                <input type="hidden" name="_token" :value="csrf">
                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_camera.email') }}</span>
                        <input type="email" name="email" required maxlength="255" class="w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <span v-if="errors.email" class="text-xs text-brand-700">{{ errors.email }}</span>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_camera.tier') }}</span>
                        <select name="tier" required class="nk-select w-full rounded-xl border border-line bg-surface px-4 py-2.5 pr-10 text-sm focus:border-brand-400 focus:outline-none">
                            <option v-for="tier in tiers" :key="tier.value" :value="tier.value">{{ tier.label }}</option>
                        </select>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_camera.amount') }}</span>
                        <input type="number" name="amount" min="0" step="0.01" class="w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                        <span class="text-xs text-ink-muted">{{ $t('admin_camera.amount_hint') }}</span>
                        <span v-if="errors.amount" class="text-xs text-brand-700">{{ errors.amount }}</span>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_camera.note') }}</span>
                        <input type="text" name="note" maxlength="255" class="w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    </label>
                </div>
                <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:self-start">{{ $t('admin_camera.grant') }}</button>
            </form>
        </details>

        <section class="flex min-w-0 flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">{{ $t('admin_camera.albums') }}</h2>
            <DataTable
                :data-url="table.dataUrl"
                :columns="table.columns"
                :filters="table.filters"
                :search-placeholder="$t('admin_camera.search')"
                :empty-title="$t('admin_camera.empty_title')"
                :empty-message="$t('admin_camera.empty_message')"
                :csrf="csrf"
                :row-action="{ inline: true }"
            />
        </section>
    </div>
</template>
