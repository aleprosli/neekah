<x-layouts.admin title="Blog" heading="Blog" subheading="Artikel yang tersiar muncul di neekah.my/blog dan dalam sitemap untuk Google.">
    <x-slot:actions>
        <a href="{{ route('admin.posts.create') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tulis artikel</a>
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminPostsPage.vue --}}
    <div
        data-vue="admin-posts-page"
        data-props="{{ json_encode([
            'posts' => $posts->items(),
            'createUrl' => route('admin.posts.create'),
            'pagination' => $posts->hasPages() ? (string) $posts->links() : '',
            'csrf' => csrf_token(),
        ]) }}"
    ></div>
</x-layouts.admin>
