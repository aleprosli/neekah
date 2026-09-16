<script setup>
/** Enquiries waiting on a reply, newest first. */
defineProps({
    enquiries: { type: Array, required: true },
    pagination: { type: String, default: '' },
});
</script>

<template>
    <p v-if="!enquiries.length" class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">
        Belum ada enquiry. Pengantin boleh menghantar enquiry dari halaman vendor anda.
    </p>

    <template v-else>
        <ul class="divide-y divide-line rounded-2xl border border-line">
            <li v-for="enquiry in enquiries" :key="enquiry.id">
                <a :href="enquiry.url" class="flex flex-col gap-1 p-4 transition hover:bg-surface-muted sm:flex-row sm:items-center sm:gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="truncate font-medium">{{ enquiry.customer }}</p>
                            <span v-if="enquiry.is_open" class="rounded-full bg-brand-600 px-2 py-0.5 text-[11px] font-semibold text-white">Baru</span>
                        </div>
                        <p class="truncate text-sm text-ink-muted">{{ enquiry.message }}</p>
                    </div>
                    <p class="shrink-0 text-xs text-ink-muted sm:text-right">
                        <template v-if="enquiry.event_date">{{ enquiry.event_date }} · </template>{{ enquiry.received }}
                    </p>
                </a>
            </li>
        </ul>

        <div v-if="pagination" class="mt-6" v-html="pagination"></div>
    </template>
</template>
