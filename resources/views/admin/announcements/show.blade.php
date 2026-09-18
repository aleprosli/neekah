<x-layouts.admin
    title="Pengumuman"
    :heading="$announcement->subject"
    :subheading="$announcement->audience->label().' · '.$announcement->status->label().($announcement->sent_at ? ' '.$announcement->sent_at->translatedFormat('j M Y, g:i A') : '')"
>
    <x-slot:actions>
        <a href="{{ route('admin.announcements.index') }}" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400">Kembali</a>
    </x-slot:actions>

    {{-- resources/js/components/admin/AdminAnnouncementDetail.vue --}}
    <div data-vue="admin-announcement-detail" data-props="@vueProps($props)"></div>
</x-layouts.admin>
