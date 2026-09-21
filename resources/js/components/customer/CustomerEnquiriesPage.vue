<script setup>
/** Questions the couple sent to vendors, newest first. */
import UiCategoryTile from '../ui/UiCategoryTile.vue';
import UiEmptyState from '../ui/UiEmptyState.vue';

defineProps({
    enquiries: { type: Array, required: true },
    findVendorsUrl: { type: String, required: true },
    pagination: { type: String, default: '' },
});
</script>

<template>
    <UiEmptyState
        v-if="!enquiries.length"
        icon="💬"
        :title="$t('customer.no_enquiries_title')"
        :message="$t('customer.tanya_vendor_tentang_pakej_tarikh')"
        :action-label="$t('common.find_vendors')"
        :action-url="findVendorsUrl"
    />

    <template v-else>
        <ul class="divide-y divide-line rounded-2xl border border-line">
            <li v-for="enquiry in enquiries" :key="enquiry.id">
                <a :href="enquiry.url" class="flex items-center gap-4 p-4 transition hover:bg-surface-muted">
                    <UiCategoryTile v-bind="enquiry.category" />

                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-medium">{{ enquiry.vendor }}</p>
                            <span v-if="enquiry.replied" class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-semibold text-emerald-800">{{ $t('customer.replied') }}</span>
                        </div>
                        <p class="truncate text-sm text-ink-muted">{{ enquiry.message }}</p>
                    </div>

                    <span class="hidden shrink-0 text-xs text-ink-muted sm:block">{{ enquiry.sent }}</span>
                </a>
            </li>
        </ul>

        <div v-if="pagination" class="mt-6" v-html="pagination"></div>
    </template>
</template>
