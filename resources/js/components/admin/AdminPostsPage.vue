<script setup>
/** The blog list: what is live, what is scheduled, what is still a draft. */
import DataTable from '../ui/DataTable.vue';
import UiConfirm from '../ui/UiConfirm.vue';

defineProps({
    posts: { type: Array, required: true },
    createUrl: { type: String, required: true },
    pagination: { type: String, default: '' },
    csrf: { type: String, required: true },
});

const COLUMNS = [
    { key: 'title', label: 'Artikel' },
    { key: 'status_label', label: 'Status' },
    { key: 'updated', label: 'Dikemas kini' },
];

const tones = {
    published: 'bg-emerald-100 text-emerald-800',
    scheduled: 'bg-gold-300/50 text-brand-900',
    draft: 'bg-surface-muted text-ink-muted',
};
</script>

<template>
    <div v-if="!posts.length" class="rounded-2xl border border-dashed border-line p-10 text-center text-sm text-ink-muted">{{ $t('admin_posts.belum_ada_artikel') }}<a :href="createUrl" class="font-medium text-brand-700 underline underline-offset-4">{{ $t('admin_posts.tulis_yang_pertama') }}</a>.
    </div>

    <template v-else>
        <DataTable :rows="posts" :columns="COLUMNS" :csrf="csrf">
            <template #cell-title="{ row }">
                <a :href="row.edit_url" class="font-medium hover:text-brand-700">{{ row.title }}</a>
                <p class="text-xs text-ink-muted">/blog/{{ row.slug }}</p>
            </template>

            <template #cell-status_label="{ row }">
                <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', tones[row.state]]">{{ row.status_label }}</span>
            </template>

            <template #action="{ row }">
                <a :href="row.public_url" target="_blank" rel="noopener" class="text-xs font-medium text-ink-muted hover:text-brand-700">
                    {{ row.state === 'published' ? 'Lihat' : 'Pratonton' }}
                </a>
                <UiConfirm
                    :action="row.destroy_url"
                    method="DELETE"
                    tone="danger"
                    :title="`Padam artikel ${row.title}?`"
                    :message="$t('admin_posts.artikel_dan_gambar_utamanya_akan')"
                    confirm-:label="$t('admin_posts.padam_artikel')"
                    trigger-class="ml-3 text-xs font-medium text-ink-muted hover:text-brand-700"
                    :csrf="csrf"
                >{{ $t('admin_posts.padam') }}</UiConfirm>
            </template>
        </DataTable>

        <div v-if="pagination" class="mt-6" v-html="pagination"></div>
    </template>
</template>
