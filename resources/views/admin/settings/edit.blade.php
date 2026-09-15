<x-layouts.admin title="Tetapan" heading="Tetapan" subheading="Kawal cara gambar yang dimuat naik diproses. Gambar yang lebih ringan menjadikan laman lebih laju untuk pelawat dan Google.">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="flex max-w-3xl flex-col gap-6 rounded-2xl border border-line bg-surface-raised p-6">
        @csrf
        @method('PUT')

        <div>
            <h2 class="font-semibold">Gambar</h2>
            <p class="mt-1 text-sm text-ink-muted">
                Setiap gambar yang dimuat naik oleh vendor, pasangan dan admin diubah saiz, dimampatkan dan dibuang data EXIF (termasuk lokasi GPS) sebelum disimpan.
                Satu salinan thumbnail turut dijana untuk senarai vendor, grid portfolio dan galeri.
            </p>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field label="Saiz maksimum (piksel, sisi terpanjang)" name="max_dimension" type="number" :value="$images['max_dimension']" min="800" max="4000" step="10" required help="1920 sudah tajam untuk skrin penuh. Lebih besar bermakna fail lebih berat." />
            <x-form.field label="Lebar thumbnail (piksel)" name="thumbnail_width" type="number" :value="$images['thumbnail_width']" min="200" max="1200" step="10" required help="Saiz yang dipaparkan dalam senarai dan grid." />
            <x-form.field label="Kualiti (40 hingga 95)" name="quality" type="number" :value="$images['quality']" min="40" max="95" required help="80 ialah titik terbaik: sukar dibezakan daripada asal, tetapi fail jauh lebih kecil." />
            <x-form.select label="Format" name="format" required help="WebP biasanya 25 hingga 35% lebih kecil daripada JPEG pada kualiti yang sama.">
                @foreach ($formats as $value => $label)
                    <option value="{{ $value }}" @selected(old('format', $images['format']) === $value)>{{ $label }}</option>
                @endforeach
            </x-form.select>
            <x-form.field label="Had saiz muat naik (MB)" name="max_upload_mb" type="number" :value="$images['max_upload_mb']" min="1" max="15" required help="Saiz fail asal yang dibenarkan sebelum diproses. Maksimum 15 MB." />
        </div>

        <p class="rounded-xl bg-surface-muted px-4 py-3 text-xs text-ink-muted">
            Tetapan ini digunakan untuk gambar yang dimuat naik selepas ini. Gambar lama diproses dengan menjalankan
            <code class="font-mono">php artisan neekah:optimize-images</code> pada server.
        </p>

        <div>
            <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan tetapan</button>
        </div>
    </form>
</x-layouts.admin>
