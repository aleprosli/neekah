<script setup>
/** Reports waiting on an admin, oldest concern first. */
defineProps({
    violations: { type: Array, required: true },
    pagination: { type: String, default: '' },
});
</script>

<template>
    <p v-if="!violations.length" class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">Tiada laporan. Bagus!</p>

    <template v-else>
        <ul class="divide-y divide-line rounded-2xl border border-line">
            <li v-for="violation in violations" :key="violation.id">
                <a :href="violation.url" class="flex flex-col gap-2 p-4 transition hover:bg-surface-muted sm:flex-row sm:items-center sm:gap-4">
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <p class="font-medium">{{ violation.vendor }}</p>
                            <span :class="['rounded-full px-2 py-0.5 text-[11px] font-semibold', violation.is_open ? 'bg-amber-100 text-amber-800' : 'bg-surface-muted text-ink-muted']">
                                {{ violation.badge }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ violation.type }} · dilaporkan oleh {{ violation.reporter }}</p>
                        <p class="truncate text-sm text-ink-muted">{{ violation.description }}</p>
                    </div>
                    <span class="shrink-0 text-xs text-ink-muted">{{ violation.reported }}</span>
                </a>
            </li>
        </ul>

        <div v-if="pagination" class="mt-6" v-html="pagination"></div>
    </template>
</template>
