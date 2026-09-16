<x-layouts.vendor title="Portfolio" heading="Portfolio" subheading="Gambar pertama menjadi gambar utama pada halaman vendor anda.">
    <form method="POST" action="{{ route('vendor.portfolio.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6 sm:flex-row sm:items-end">
        @csrf
        <label class="flex flex-1 flex-col gap-1.5">
            <span class="text-sm font-medium">Muat naik gambar</span>
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700">
            <x-form.image-hint recommended="1600 × 1200px atau lebih" note="Sehingga 10 gambar sekali gus. Gambar pertama menjadi gambar utama." />
        </label>
        <x-form.field label="Kapsyen (pilihan)" name="caption" placeholder="Majlis Aina & Hakim, Alor Setar" class="sm:w-64" />
        <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Muat naik</button>
    </form>

    @if ($items->isEmpty())
        <p class="mt-8 rounded-2xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">Belum ada gambar. Portfolio yang menarik menaikkan kadar tempahan.</p>
    @else
        {{-- resources/js/components/vendor/PortfolioManager.vue --}}
        <div
            class="mt-8"
            data-vue="portfolio-manager"
            data-props="{{ json_encode([
                'items' => $items,
                'reorderUrl' => route('vendor.portfolio.reorder'),
                'destroyUrlTemplate' => route('vendor.portfolio.destroy', ['item' => '__ID__']),
                'csrf' => csrf_token(),
            ]) }}"
        >
            {{-- Without JavaScript the vendor still sees and can delete their photos. --}}
            <ul class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($items as $item)
                    <li class="overflow-hidden rounded-2xl border border-line">
                        <img src="{{ $item['thumbnail'] ?: $item['url'] }}" alt="{{ $item['caption'] }}" loading="lazy" class="aspect-square w-full object-cover">
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

</x-layouts.vendor>
