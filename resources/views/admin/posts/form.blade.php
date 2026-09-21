<x-layouts.admin :title="$isNew ? __('pages.dash.artikel_baru') : __('pages.dash.sunting_artikel')" :heading="$isNew ? __('pages.dash.artikel_baru') : __('pages.dash.sunting_artikel')" :subheading="__('pages.dash.tulis_susun_dan_siarkan_artikel')">
    @unless ($isNew)
        <x-slot:actions>
            <a href="{{ $post->url() }}" target="_blank" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">{{ $post->isPublished() ? 'Lihat artikel' : 'Pratonton' }}</a>
        </x-slot:actions>
    @endunless

    {{-- resources/js/components/admin/AdminPostForm.vue --}}
    <div data-vue="admin-post-form" data-props="@vueProps($props)"></div>
</x-layouts.admin>
