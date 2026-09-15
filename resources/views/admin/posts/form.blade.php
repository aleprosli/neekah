@php
    $isNew = ! $post->exists;
    $status = old('status', $post->published_at ? 'published' : 'draft');
    $toolbar = [
        ['h2', 'H2', 'Tajuk bahagian (H2)'],
        ['h3', 'H3', 'Subtajuk (H3)'],
        ['paragraph', '¶', 'Perenggan biasa'],
        null,
        ['bold', 'B', 'Tebal'],
        ['italic', 'I', 'Condong'],
        ['underline', 'U', 'Garis bawah'],
        ['strike', 'S', 'Garis tengah'],
        null,
        ['bulletList', '• Senarai', 'Senarai titik'],
        ['orderedList', '1. Senarai', 'Senarai bernombor'],
        ['blockquote', '❝', 'Petikan'],
        ['horizontalRule', '―', 'Garisan pemisah'],
        null,
        ['link', '🔗 Pautan', 'Tambah atau buang pautan'],
        ['image', '🖼️ Gambar', 'Masukkan gambar'],
        null,
        ['undo', '↶', 'Buat asal'],
        ['redo', '↷', 'Buat semula'],
    ];
@endphp

<x-layouts.admin :title="$isNew ? 'Artikel baru' : 'Sunting artikel'" :heading="$isNew ? 'Artikel baru' : 'Sunting artikel'" subheading="Tulis, susun dan siarkan artikel di neekah.my/blog.">
    @unless ($isNew)
        <x-slot:actions>
            <a href="{{ $post->url() }}" target="_blank" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">{{ $post->isPublished() ? 'Lihat artikel' : 'Pratonton' }}</a>
        </x-slot:actions>
    @endunless

    <form method="POST" action="{{ $isNew ? route('admin.posts.store') : route('admin.posts.update', $post) }}" enctype="multipart/form-data" class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]">
        @csrf
        @unless ($isNew)
            @method('PUT')
        @endunless

        <div class="flex min-w-0 flex-col gap-5">
            <x-form.field label="Tajuk" name="title" :value="$post->title" required maxlength="160" placeholder="Contoh: 10 Tips Memilih Pelamin untuk Majlis Kecil" />

            <div class="flex flex-col gap-1.5" data-rich-editor data-upload-url="{{ route('admin.posts.images.store') }}">
                <span class="text-sm font-medium">Isi artikel</span>
                <div class="rounded-2xl border border-line bg-surface focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-400/40">
                    <div class="sticky top-0 z-10 flex flex-wrap gap-1 rounded-t-2xl border-b border-line bg-surface-muted/90 p-2 backdrop-blur" role="toolbar" aria-label="Format teks">
                        @foreach ($toolbar as $button)
                            @if ($button === null)
                                <span class="mx-1 w-px self-stretch bg-line" aria-hidden="true"></span>
                            @else
                                <button type="button" data-editor-command="{{ $button[0] }}" title="{{ $button[2] }}" aria-label="{{ $button[2] }}" class="min-w-9 rounded-lg px-2.5 py-1.5 text-sm font-semibold transition hover:bg-surface">{{ $button[1] }}</button>
                            @endif
                        @endforeach
                    </div>
                    <div data-editor-content></div>
                </div>
                <input type="hidden" name="body" value="{{ old('body', $post->body) }}" data-editor-input>
                <input type="file" accept="image/jpeg,image/png,image/webp" class="hidden" data-editor-file>
                <p class="flex justify-between gap-4 text-xs text-ink-muted">
                    <span>Guna H2 untuk setiap bahagian utama dan H3 di bawahnya. Google membaca struktur ini.</span>
                    <span data-editor-status class="shrink-0"></span>
                </p>
            </div>

            <x-form.textarea label="Ringkasan" name="excerpt" :value="$post->excerpt" rows="3" maxlength="300" help="Dipaparkan dalam senarai blog. Jika kosong, pembukaan artikel digunakan." />
        </div>

        <aside class="flex flex-col gap-5">
            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">Siaran</h2>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="status" value="draft" class="accent-brand-600" @checked($status === 'draft')>
                    Draf
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="radio" name="status" value="published" class="accent-brand-600" @checked($status === 'published')>
                    Siarkan
                </label>
                <x-form.field label="Tarikh siaran" name="published_at" type="datetime-local" :value="$post->localPublishedAt()?->format('Y-m-d\TH:i')" help="Waktu Malaysia. Kosongkan untuk siar sekarang; tarikh akan datang menjadikannya dijadualkan." />
                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan</button>
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">Gambar utama</h2>
                @if ($post->cover_image)
                    <img src="{{ $post->coverThumbnailUrl() }}" alt="" class="aspect-[16/10] w-full rounded-xl object-cover">
                @endif
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700">
                <p class="text-xs text-ink-muted">Juga menjadi gambar pratonton apabila pautan dikongsi di WhatsApp dan Facebook. Nisbah 16:9 paling sesuai.</p>
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">SEO</h2>
                <x-form.field label="Slug URL" name="slug" :value="$post->slug" maxlength="180" help="neekah.my/blog/slug-anda. Kosongkan untuk jana daripada tajuk." />
                <x-form.field label="Tajuk SEO" name="meta_title" :value="$post->meta_title" maxlength="70" help="Tajuk dalam hasil carian Google, sekitar 60 aksara. Kosongkan untuk guna tajuk artikel." />
                <x-form.textarea label="Deskripsi SEO" name="meta_description" :value="$post->meta_description" rows="3" maxlength="170" help="Ayat di bawah tajuk dalam Google, sekitar 160 aksara. Masukkan kata kunci utama." />
            </section>
        </aside>
    </form>
</x-layouts.admin>
