<x-layouts.admin :title="__('pages.dash.blog')" :heading="__('pages.dash.blog_2')" :subheading="__('pages.dash.artikel_yang_tersiar_muncul_di')">
    <x-slot:actions>
        <a href="{{ route('admin.posts.create') }}" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.dash.tulis_artikel') }}</a>
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminPostsPage.vue --}}
    <div
        data-vue="admin-posts-page"
        data-props="@vueProps([
            'posts' => $posts->items(),
            'createUrl' => route('admin.posts.create'),
            'pagination' => $posts->hasPages() ? (string) $posts->onEachSide(1)->links() : '',
            'csrf' => csrf_token(),
        ])"
    ></div>
</x-layouts.admin>
