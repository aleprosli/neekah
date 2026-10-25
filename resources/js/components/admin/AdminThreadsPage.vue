<script setup>
/**
 * Neekah's Threads account in one page: whether it is connected, the posts
 * on their way out (grouped by day, each editable until it goes), the
 * template bank the planner writes from, what already went out, and the
 * daily plan itself.
 *
 * Posts are planned by the server (PlanThreadsPosts) and sent a minute at a
 * time by neekah:threads-publish; nothing here talks to Threads directly.
 */
import { computed, ref, watch } from 'vue';
import { t } from '../../i18n.js';
import AdminStatRow from './AdminStatRow.vue';
import DataTable from '../ui/DataTable.vue';
import UiBadge from '../ui/UiBadge.vue';
import UiConfirm from '../ui/UiConfirm.vue';
import ThreadsPlanSettings from './threads/ThreadsPlanSettings.vue';
import ThreadsPostEditor from './threads/ThreadsPostEditor.vue';
import ThreadsTemplateBank from './threads/ThreadsTemplateBank.vue';

const props = defineProps({
    connection: { type: Object, required: true },
    settings: { type: Object, required: true },
    types: { type: Array, required: true },
    stats: { type: Array, required: true },
    upcoming: { type: Array, required: true },
    templates: { type: Array, required: true },
    limits: { type: Object, required: true },
    urls: { type: Object, required: true },
    statusFilter: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const TAB_KEY = 'admin-threads-tab';
const tabs = ['queue', 'templates', 'history', 'settings'];

const readTab = () => {
    try {
        const stored = window.sessionStorage.getItem(TAB_KEY);
        return tabs.includes(stored) ? stored : 'queue';
    } catch {
        return 'queue';
    }
};

const tab = ref(readTab());

// The page reloads after every save; come back to the tab that was open.
watch(tab, (value) => {
    try {
        window.sessionStorage.setItem(TAB_KEY, value);
    } catch {
        // Private mode: the first tab is a fine place to land.
    }
});

/** The upcoming posts, one group per day. */
const days = computed(() => {
    const groups = [];

    for (const post of props.upcoming) {
        const last = groups[groups.length - 1];

        if (last && last.day === post.day) {
            last.posts.push(post);
        } else {
            groups.push({ day: post.day, label: post.day_label, posts: [post] });
        }
    }

    return groups;
});

/** null = closed; {} = a new post; a row = editing that post. */
const editing = ref(null);

const historyColumns = [
    { key: 'when', label: t('admin_threads.col_masa') },
    { key: 'type_label', label: t('admin_threads.col_jenis') },
    { key: 'text', label: t('admin_threads.col_post') },
    { key: 'status_label', label: t('admin_threads.col_status') },
];

const tabLabel = (key) => ({
    queue: 'admin_threads.tab_jadual',
    templates: 'admin_threads.tab_templat',
    history: 'admin_threads.tab_sejarah',
    settings: 'admin_threads.tab_tetapan',
})[key];
</script>

<template>
    <div class="flex min-w-0 flex-col gap-6 break-words">
        <!-- The account. Nothing goes out until this says connected and the switch is on. -->
        <section class="flex min-w-0 flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-5 shadow-sm sm:flex-row sm:items-center sm:p-6">
            <span class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-ink text-white" aria-hidden="true">
                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M16.5 11.2c-.6-3-2.6-4.4-5-4.2-2.6.2-4.3 2.4-4.3 5.3 0 3.3 2 5.6 5 5.6 2.6 0 4.6-1.6 4.6-3.8 0-2-1.5-3.2-3.6-3.2-1.8 0-3 1-3 2.3 0 1.1.9 1.8 2 1.8 1.6 0 2.4-1.4 2.4-3.6" /><path d="M19.5 7.5C18 4.5 15.3 3 12 3 6.8 3 3.5 6.8 3.5 12s3.3 9 8.5 9c3.6 0 6.2-1.7 7.5-4.5" /></svg>
            </span>

            <div class="min-w-0 flex-1">
                <template v-if="connection.connected">
                    <p class="flex flex-wrap items-center gap-2 font-semibold">
                        <a :href="connection.profile_url" target="_blank" rel="noopener" class="hover:text-brand-700">@{{ connection.username }}</a>
                        <UiBadge :label="settings.running ? $t('admin_threads.auto_post_aktif') : $t('admin_threads.auto_post_berhenti')" :tone="settings.running ? 'emerald' : 'amber'" />
                    </p>
                    <p class="mt-0.5 text-sm text-ink-muted">{{ $t('admin_threads.token_sah_hingga', { date: connection.expires }) }}</p>
                </template>
                <template v-else>
                    <p class="font-semibold">{{ connection.expired ? $t('admin_threads.sambungan_tamat') : $t('admin_threads.belum_disambung') }}</p>
                    <p class="mt-0.5 text-sm text-ink-muted">
                        {{ connection.configured ? $t('admin_threads.sambung_untuk_mula') : $t('admin_threads.app_belum_ditetapkan') }}
                    </p>
                    <p v-if="connection.configured" class="mt-2 text-xs text-ink-muted">
                        {{ $t('admin_threads.redirect_uri') }} <code class="rounded bg-surface-muted px-1.5 py-0.5 break-all">{{ connection.redirect_uri }}</code>
                    </p>
                </template>
            </div>

            <div class="flex shrink-0 flex-wrap gap-2">
                <a
                    v-if="connection.configured"
                    :href="connection.connect_url"
                    :class="['inline-flex h-10 items-center rounded-full px-5 text-sm font-semibold transition', connection.connected ? 'border border-line hover:border-brand-400' : 'bg-brand-600 text-white hover:bg-brand-700']"
                    data-no-swap
                >{{ connection.connected ? $t('admin_threads.sambung_semula') : $t('admin_threads.sambung_threads') }}</a>
                <UiConfirm
                    v-if="connection.connected"
                    :action="connection.disconnect_url"
                    method="DELETE"
                    tone="danger"
                    :title="$t('admin_threads.putuskan_tajuk', { username: connection.username })"
                    :message="$t('admin_threads.putuskan_mesej')"
                    :confirm-label="$t('admin_threads.putuskan')"
                    :cancel-label="$t('admin_threads.batal')"
                    :label="$t('admin_threads.putuskan')"
                    :csrf="csrf"
                    trigger-class="inline-flex h-10 items-center rounded-full border border-line px-5 text-sm font-medium transition hover:border-red-300 hover:text-red-700"
                />
            </div>
        </section>

        <AdminStatRow :stats="stats" columns="grid-cols-1 sm:grid-cols-3" />

        <div class="no-scrollbar -mx-1 flex min-w-0 gap-1 overflow-x-auto px-1" role="tablist">
            <button
                v-for="key in tabs"
                :key="key"
                type="button"
                role="tab"
                :aria-selected="tab === key"
                :class="['h-9 shrink-0 rounded-full px-4 text-sm font-semibold transition', tab === key ? 'bg-ink text-white' : 'text-ink-muted hover:bg-surface-muted hover:text-ink']"
                @click="tab = key"
            >{{ $t(tabLabel(key)) }}</button>
        </div>

        <!-- Jadual: what goes out next, by day. -->
        <section v-if="tab === 'queue'" class="flex min-w-0 flex-col gap-5">
            <div class="flex min-w-0 flex-wrap items-center gap-2">
                <p class="min-w-0 flex-1 text-sm text-ink-muted">{{ $t('admin_threads.jadual_bantuan') }}</p>
                <form :action="urls.plan" method="POST">
                    <input type="hidden" name="_token" :value="csrf">
                    <button type="submit" class="inline-flex h-10 items-center rounded-full border border-line px-4 text-sm font-semibold transition hover:border-brand-400">{{ $t('admin_threads.isi_slot_kosong') }}</button>
                </form>
                <button type="button" class="inline-flex h-10 items-center gap-2 rounded-full bg-brand-600 px-4 text-sm font-semibold text-white transition hover:bg-brand-700" @click="editing = {}">
                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                    {{ $t('admin_threads.tulis_post') }}
                </button>
            </div>

            <div v-if="!days.length" class="flex flex-col items-center gap-2 rounded-3xl border border-dashed border-line px-6 py-14 text-center">
                <h3 class="font-semibold">{{ $t('admin_threads.jadual_kosong') }}</h3>
                <p class="max-w-md text-sm text-ink-muted">{{ $t('admin_threads.jadual_kosong_mesej') }}</p>
            </div>

            <div v-for="group in days" :key="group.day" class="flex min-w-0 flex-col gap-3">
                <h3 class="font-display text-lg font-semibold">{{ group.label }}</h3>

                <ul class="flex min-w-0 flex-col gap-3">
                    <li v-for="post in group.posts" :key="post.id" class="flex min-w-0 gap-4 rounded-2xl border border-line bg-surface-raised p-4">
                        <div class="w-16 shrink-0 text-sm font-semibold">{{ post.time_label }}</div>

                        <div class="flex min-w-0 flex-1 flex-col gap-2">
                            <div class="flex min-w-0 flex-wrap items-center gap-2">
                                <UiBadge :label="post.type_label" :tone="post.type_tone" />
                                <UiBadge :label="post.status_label" :tone="post.status_tone" />
                                <span v-if="post.vendor" class="truncate text-xs text-ink-muted">{{ post.vendor }}</span>
                                <span v-if="!post.auto" class="text-xs text-ink-muted">{{ $t('admin_threads.ditulis_admin') }}</span>
                            </div>

                            <div class="flex min-w-0 gap-3">
                                <img v-if="post.image" :src="post.image" alt="" class="size-20 shrink-0 rounded-xl object-cover ring-1 ring-line" loading="lazy">
                                <p class="min-w-0 flex-1 text-sm whitespace-pre-line">{{ post.text }}</p>
                            </div>

                            <ul v-if="post.poll_options.some(Boolean)" class="flex flex-wrap gap-1.5">
                                <li v-for="option in post.poll_options.filter(Boolean)" :key="option" class="rounded-full border border-line px-3 py-1 text-xs">{{ option }}</li>
                            </ul>

                            <p v-if="post.error" class="rounded-xl bg-red-50 px-3 py-2 text-xs text-red-800">{{ post.error }}</p>

                            <div class="flex flex-wrap items-center gap-2">
                                <a v-if="post.permalink" :href="post.permalink" target="_blank" rel="noopener" class="text-xs font-semibold text-brand-700 hover:underline">{{ $t('admin_threads.lihat_di_threads') }}</a>
                                <template v-if="post.editable">
                                    <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="editing = post">{{ $t('admin_threads.edit') }}</button>
                                    <form v-if="post.rewrite_url" :action="post.rewrite_url" method="POST">
                                        <input type="hidden" name="_token" :value="csrf">
                                        <button type="submit" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400">{{ $t('admin_threads.tulis_semula') }}</button>
                                    </form>
                                    <UiConfirm
                                        :action="post.publish_url"
                                        :title="$t('admin_threads.terbit_sekarang_tajuk')"
                                        :message="$t('admin_threads.terbit_sekarang_mesej')"
                                        :confirm-label="$t('admin_threads.terbit_sekarang')"
                                        :cancel-label="$t('admin_threads.batal')"
                                        :label="$t('admin_threads.terbit_sekarang')"
                                        :csrf="csrf"
                                    />
                                    <UiConfirm
                                        :action="post.cancel_url"
                                        method="DELETE"
                                        tone="danger"
                                        :title="$t('admin_threads.batalkan_tajuk', { time: post.when })"
                                        :message="$t('admin_threads.batalkan_mesej')"
                                        :confirm-label="$t('admin_threads.batalkan')"
                                        :cancel-label="$t('admin_threads.batal')"
                                        :label="$t('admin_threads.batalkan')"
                                        :csrf="csrf"
                                        trigger-class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-red-300 hover:text-red-700"
                                    />
                                </template>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <ThreadsTemplateBank
            v-else-if="tab === 'templates'"
            :templates="templates"
            :types="types"
            :limits="limits"
            :store-url="urls.store_template"
            :csrf="csrf"
            :errors="errors"
        />

        <DataTable
            v-else-if="tab === 'history'"
            :data-url="urls.history"
            :columns="historyColumns"
            :filters="[statusFilter]"
            :search-placeholder="$t('admin_threads.cari_post')"
            :empty-title="$t('admin_threads.sejarah_kosong')"
            :csrf="csrf"
        >
            <template #cell-type_label="{ row }"><UiBadge :label="row.type_label" :tone="row.type_tone" /></template>
            <template #cell-text="{ row }">
                <span class="line-clamp-2 max-w-md">{{ row.text }}</span>
                <span v-if="row.error" class="mt-1 block text-xs text-red-700">{{ row.error }}</span>
            </template>
            <template #cell-status_label="{ row }">
                <span class="flex flex-wrap items-center gap-2">
                    <UiBadge :label="row.status_label" :tone="row.status_tone" />
                    <a v-if="row.permalink" :href="row.permalink" target="_blank" rel="noopener" class="text-xs font-semibold text-brand-700 hover:underline">{{ $t('admin_threads.lihat') }}</a>
                </span>
            </template>
        </DataTable>

        <ThreadsPlanSettings
            v-else
            :settings="settings"
            :types="types"
            :limits="limits"
            :connected="connection.connected"
            :csrf="csrf"
            :errors="errors"
        />

        <ThreadsPostEditor
            v-if="editing"
            :post="editing.id ? editing : null"
            :types="types"
            :limits="limits"
            :store-url="urls.store_post"
            :csrf="csrf"
            :errors="errors"
            @close="editing = null"
        />
    </div>
</template>
