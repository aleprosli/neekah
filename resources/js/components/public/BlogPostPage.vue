<script setup>
/**
 * One article. The body is trusted markup: App\Support\HtmlSanitizer cleaned it
 * when the admin saved it, and nothing else may write to that column.
 */
defineProps({
    post: { type: Object, required: true },
    related: { type: Array, default: () => [] },
    blogUrl: { type: String, required: true },
});
</script>

<template>
    <article class="mx-auto max-w-3xl">
        <p v-if="post.draft" class="mb-6 rounded-2xl border border-gold-400 bg-gold-300/40 px-5 py-3 text-sm text-brand-900">
            Pratonton draf. Hanya admin boleh melihat halaman ini sehingga artikel disiarkan.
        </p>

        <nav :aria-label="$t('blog.breadcrumb')" class="text-xs font-semibold tracking-wide text-brand-600 uppercase">
            <a :href="blogUrl" class="hover:text-brand-800">{{ $t('common.blog') }}</a>
        </nav>

        <h1 class="mt-3 font-display text-3xl font-semibold tracking-tight text-balance sm:text-4xl lg:text-5xl">{{ post.title }}</h1>

        <p class="mt-4 text-sm text-ink-muted">
            <template v-if="post.published"><time :datetime="post.published_iso">{{ post.published }}</time> · </template>
            {{ post.reading }} min bacaan
        </p>

        <img v-if="post.cover" :src="post.cover" :alt="post.title" fetchpriority="high" decoding="async" class="mt-8 aspect-[16/9] w-full rounded-3xl object-cover">

        <div class="nk-prose mt-10" v-html="post.body"></div>
    </article>

    <section v-if="related.length" class="mx-auto mt-20 max-w-6xl border-t border-line pt-10" aria-labelledby="baca-juga">
        <h2 id="baca-juga" class="font-display text-2xl font-semibold">{{ $t('blog.read_next') }}</h2>

        <div class="mt-6 grid gap-8 sm:grid-cols-3">
            <a v-for="other in related" :key="other.url" :href="other.url" class="group flex flex-col gap-3">
                <div class="aspect-[16/10] overflow-hidden rounded-2xl bg-linear-to-br from-brand-100 to-gold-300">
                    <img v-if="other.cover" :src="other.cover" :alt="other.title" loading="lazy" decoding="async" class="size-full object-cover">
                </div>
                <h3 class="font-display text-lg leading-snug font-semibold group-hover:text-brand-700">{{ other.title }}</h3>
            </a>
        </div>
    </section>
</template>
