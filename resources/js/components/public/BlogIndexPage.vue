<script setup>
/** The article list. */
defineProps({
    posts: { type: Array, required: true },
    pagination: { type: String, default: '' },
});
</script>

<template>
    <p v-if="!posts.length" class="mt-10 rounded-2xl border border-dashed border-line p-10 text-center text-ink-muted">
        Artikel pertama akan tersiar tidak lama lagi.
    </p>

    <template v-else>
        <div class="mt-10 grid gap-x-8 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
            <article v-for="post in posts" :key="post.url" class="group">
                <a :href="post.url" class="flex flex-col gap-3">
                    <div class="aspect-[16/10] overflow-hidden rounded-2xl bg-linear-to-br from-brand-100 to-gold-300">
                        <img v-if="post.cover" :src="post.cover" :alt="post.title" loading="lazy" decoding="async" class="size-full object-cover transition duration-500 group-hover:scale-[1.03]">
                    </div>

                    <p class="text-xs text-ink-muted">
                        <time :datetime="post.published_iso">{{ post.published }}</time> · {{ post.reading }} min bacaan
                    </p>

                    <h2 class="font-display text-xl leading-snug font-semibold transition group-hover:text-brand-700">{{ post.title }}</h2>
                    <p class="line-clamp-3 text-sm text-ink-muted">{{ post.summary }}</p>
                </a>
            </article>
        </div>

        <div v-if="pagination" class="mt-12" v-html="pagination"></div>
    </template>
</template>
