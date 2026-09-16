<script setup>
/** The blog list: what is live, what is scheduled, what is still a draft. */
import UiConfirm from '../ui/UiConfirm.vue';

defineProps({
    posts: { type: Array, required: true },
    createUrl: { type: String, required: true },
    pagination: { type: String, default: '' },
    csrf: { type: String, required: true },
});

const tones = {
    published: 'bg-emerald-100 text-emerald-800',
    scheduled: 'bg-gold-300/50 text-brand-900',
    draft: 'bg-surface-muted text-ink-muted',
};
</script>

<template>
    <div v-if="!posts.length" class="rounded-2xl border border-dashed border-line p-10 text-center text-sm text-ink-muted">
        Belum ada artikel. <a :href="createUrl" class="font-medium text-brand-700 underline underline-offset-4">Tulis yang pertama</a>.
    </div>

    <template v-else>
        <div class="min-w-0 overflow-x-auto rounded-2xl border border-line">
            <table class="w-full min-w-[640px] text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Artikel</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3 font-semibold">Dikemas kini</th>
                        <th class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-line">
                    <tr v-for="post in posts" :key="post.id">
                        <td class="px-4 py-3">
                            <a :href="post.edit_url" class="font-medium hover:text-brand-700">{{ post.title }}</a>
                            <p class="text-xs text-ink-muted">/blog/{{ post.slug }}</p>
                        </td>

                        <td class="px-4 py-3">
                            <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', tones[post.state]]">{{ post.status_label }}</span>
                        </td>

                        <td class="px-4 py-3 text-ink-muted">{{ post.updated }}</td>

                        <td class="space-x-3 px-4 py-3 text-right whitespace-nowrap">
                            <a :href="post.public_url" target="_blank" rel="noopener" class="text-xs font-medium text-ink-muted hover:text-brand-700">
                                {{ post.state === 'published' ? 'Lihat' : 'Pratonton' }}
                            </a>
                            <UiConfirm
                                :action="post.destroy_url"
                                method="DELETE"
                                tone="danger"
                                :title="`Padam artikel ${post.title}?`"
                                message="Artikel dan gambar utamanya akan dipadam. Tindakan ini tidak boleh dibatalkan."
                                confirm-label="Padam artikel"
                                trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                                :csrf="csrf"
                            >Padam</UiConfirm>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="pagination" class="mt-6" v-html="pagination"></div>
    </template>
</template>
