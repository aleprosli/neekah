<x-layouts.admin :title="$isNew ? 'Artikel baru' : 'Sunting artikel'" :heading="$isNew ? 'Artikel baru' : 'Sunting artikel'" subheading="Tulis, susun dan siarkan artikel di neekah.my/blog.">
    @unless ($isNew)
        <x-slot:actions>
            <a href="{{ $post->url() }}" target="_blank" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">{{ $post->isPublished() ? 'Lihat artikel' : 'Pratonton' }}</a>
        </x-slot:actions>
    @endunless

    {{-- resources/js/components/admin/AdminPostForm.vue --}}
    <div data-vue="admin-post-form" data-props="{{ json_encode($props) }}"></div>
</x-layouts.admin>
