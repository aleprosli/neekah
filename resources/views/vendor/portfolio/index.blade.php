<x-layouts.vendor title="Portfolio" heading="Portfolio" subheading="Gambar pertama menjadi gambar utama pada halaman vendor anda.">
    <form method="POST" action="{{ route('vendor.portfolio.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6 sm:flex-row sm:items-end">
        @csrf
        <label class="flex flex-1 flex-col gap-1.5">
            <span class="text-sm font-medium">Muat naik gambar</span>
            <input type="file" name="images[]" accept="image/*" multiple required class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700">
            <span class="text-xs text-ink-muted">Sehingga 10 gambar sekali gus, maksimum 4MB setiap satu.</span>
        </label>
        <x-form.field label="Kapsyen (pilihan)" name="caption" placeholder="Majlis Aina & Hakim, Alor Setar" class="sm:w-64" />
        <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Muat naik</button>
    </form>

    @if ($items->isEmpty())
        <p class="mt-8 rounded-2xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">Belum ada gambar. Portfolio yang menarik menaikkan kadar tempahan.</p>
    @else
        <ul class="mt-8 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($items as $item)
                <li class="group relative overflow-hidden rounded-2xl border border-line">
                    <img src="{{ $item->url() }}" alt="{{ $item->caption }}" class="aspect-square w-full object-cover">
                    @if ($item->caption)
                        <p class="truncate px-3 py-2 text-xs text-ink-muted">{{ $item->caption }}</p>
                    @endif
                    <form method="POST" action="{{ route('vendor.portfolio.destroy', $item) }}" class="absolute top-2 right-2" onsubmit="return confirm('Padam gambar ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="flex size-8 items-center justify-center rounded-full bg-black/60 text-white opacity-0 transition group-hover:opacity-100 focus:opacity-100" aria-label="Padam">✕</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif
</x-layouts.vendor>
