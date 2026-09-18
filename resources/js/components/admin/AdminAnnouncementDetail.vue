<script setup>
/** What was sent, to whom, and when — the record of one announcement. */
import UiBadge from '../ui/UiBadge.vue';

defineProps({
    announcement: { type: Object, required: true },
    facts: { type: Array, required: true },
    recipients: { type: Array, required: true },
});
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_320px]">
        <article class="min-w-0 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-display text-xl font-semibold">{{ announcement.subject }}</h2>

            <p v-for="(paragraph, at) in announcement.paragraphs" :key="at" class="mt-3 text-sm whitespace-pre-line">{{ paragraph }}</p>

            <a
                v-if="announcement.action_url"
                :href="announcement.action_url"
                class="mt-5 inline-flex rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700"
            >{{ announcement.action_label }}</a>
        </article>

        <aside class="flex min-w-0 flex-col gap-4">
            <dl class="flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-6 text-sm">
                <div v-for="fact in facts" :key="fact.label" class="min-w-0">
                    <dt class="text-ink-muted">{{ fact.label }}</dt>
                    <dd v-if="fact.tone" class="mt-1"><UiBadge :label="fact.value" :tone="fact.tone" /></dd>
                    <dd v-else class="font-medium break-words">{{ fact.value }}</dd>
                </div>
            </dl>

            <section v-if="recipients.length" class="flex min-w-0 flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-6 text-sm">
                <h2 class="text-sm font-semibold">Senarai pilihan ({{ recipients.length }})</h2>
                <ul class="flex min-w-0 flex-col gap-1">
                    <li v-for="person in recipients" :key="person.email" class="min-w-0 break-words">
                        {{ person.name }}
                        <span class="text-ink-muted">· {{ person.email }}</span>
                    </li>
                </ul>
            </section>
        </aside>
    </div>
</template>
