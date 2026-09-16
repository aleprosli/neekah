<script setup>
/** Everything the platform has told this account, newest first. */
import UiEmptyState from '../ui/UiEmptyState.vue';

defineProps({
    notifications: { type: Array, required: true },
    unreadCount: { type: Number, default: 0 },
    readAllUrl: { type: String, required: true },
    pagination: { type: String, default: '' },
    notice: { type: String, default: null },
    csrf: { type: String, required: true },
});
</script>

<template>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="font-display text-3xl font-semibold tracking-tight">Notifikasi</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ unreadCount ? `${unreadCount} belum dibaca` : 'Semua telah dibaca' }}</p>
        </div>

        <form v-if="unreadCount" :action="readAllUrl" method="POST">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">
            <button type="submit" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Tandakan semua dibaca</button>
        </form>
    </div>

    <p v-if="notice" class="mt-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900">{{ notice }}</p>

    <UiEmptyState
        v-if="!notifications.length"
        class="mt-8"
        icon="🔔"
        title="Belum ada notifikasi"
        message="Tempahan, pembayaran dan balasan enquiry akan muncul di sini."
    />

    <template v-else>
        <ul class="mt-8 divide-y divide-line rounded-2xl border border-line">
            <li v-for="notification in notifications" :key="notification.id" :class="['transition', notification.unread ? 'bg-brand-50/40' : '']">
                <a :href="notification.url" class="flex gap-4 p-4 hover:bg-surface-muted">
                    <span class="text-xl leading-none">{{ notification.icon }}</span>

                    <div class="min-w-0 flex-1">
                        <p class="font-medium">{{ notification.title }}</p>
                        <p class="text-sm text-ink-muted">{{ notification.body }}</p>
                        <p class="mt-0.5 text-xs text-ink-muted">{{ notification.at }}</p>
                    </div>

                    <span v-if="notification.unread" class="mt-1.5 size-2 shrink-0 rounded-full bg-brand-600" aria-label="Belum dibaca"></span>
                </a>
            </li>
        </ul>

        <div v-if="pagination" class="mt-6" v-html="pagination"></div>
    </template>
</template>
