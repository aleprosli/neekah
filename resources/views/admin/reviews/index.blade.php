<x-layouts.admin title="Review" heading="Review" subheading="Semua review pada setiap profil vendor, termasuk yang sudah ditarik.">
    <div class="no-scrollbar -mx-4 mb-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:px-0">
        <a href="{{ route('admin.reviews.index') }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => ! $filter, 'border-line hover:border-brand-400' => $filter])>Semua ({{ $total }})</a>
        @foreach ($filters as ['filter' => $case, 'total' => $count])
            <a href="{{ route('admin.reviews.index', ['filter' => $case->value]) }}" @class(['rounded-full border px-4 py-1.5 text-sm font-medium whitespace-nowrap', 'border-brand-600 bg-brand-600 text-white' => $filter === $case, 'border-line hover:border-brand-400' => $filter !== $case])>{{ $case->label() }} ({{ $count }})</a>
        @endforeach
    </div>

    <p class="mb-6 text-sm text-ink-muted">
        {{ $filter?->description() ?? 'Review dari tempahan menggerakkan rating dan ranking vendor. Review terbuka tidak. Menyembunyikan boleh diundur; memadam tidak.' }}
    </p>

    {{-- Carrying over a review the vendor already had elsewhere. --}}
    <details class="mb-6 min-w-0 rounded-2xl border border-line bg-surface-muted/40 p-4 sm:p-6">
        <summary class="cursor-pointer text-sm font-semibold">Tambah review secara manual</summary>

        <p class="mt-2 text-sm text-ink-muted">
            Untuk memindahkan review sedia ada vendor dari tempat lain. Ia disimpan sebagai review terbuka, tidak memberi mata atau menaikkan ranking, dan dicatat atas nama anda.
        </p>

        <form method="POST" action="{{ route('admin.reviews.store') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-4">
            @csrf

            @if ($errors->any())
                <ul class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">Vendor</span>
                    <select name="vendor_id" required class="nk-select w-full rounded-xl border border-line bg-surface px-4 py-3 pr-10 text-sm focus:border-brand-400 focus:outline-none">
                        <option value="">Pilih vendor…</option>
                        @foreach ($props['vendors'] as $option)
                            <option value="{{ $option['id'] }}" @selected((int) old('vendor_id') === $option['id'])>{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">Nama penulis</span>
                    <input type="text" name="author_name" value="{{ old('author_name') }}" required minlength="2" maxlength="80" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>

                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">Emel penulis <span class="font-normal normal-case opacity-70">(pilihan)</span></span>
                    <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="255" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>

                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">Tarikh asal <span class="font-normal normal-case opacity-70">(pilihan)</span></span>
                    <input type="date" name="written_on" value="{{ old('written_on') }}" max="{{ now()->toDateString() }}" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>

            <fieldset class="min-w-0">
                <legend class="text-xs font-semibold tracking-wide uppercase">Berapa bintang?</legend>
                <div class="mt-1 flex flex-row-reverse justify-end">
                    @foreach ([5, 4, 3, 2, 1] as $value)
                        <input type="radio" id="admin-review-star-{{ $value }}" name="rating" value="{{ $value }}" class="peer sr-only" required @checked((int) old('rating') === $value)>
                        <label for="admin-review-star-{{ $value }}" class="cursor-pointer px-1 py-1.5 text-3xl leading-none text-line transition peer-checked:text-gold-500 peer-focus-visible:outline peer-focus-visible:outline-brand-600">
                            <span aria-hidden="true">★</span>
                            <span class="sr-only">{{ $value }} bintang</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="flex min-w-0 flex-col gap-1">
                <span class="text-xs font-semibold tracking-wide uppercase">Ulasan</span>
                <textarea name="comment" rows="3" required minlength="10" maxlength="1000" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">{{ old('comment') }}</textarea>
            </label>

            <div data-vue="ui-photo-picker" data-props="@vueProps(['name' => 'photos[]', 'max' => $props['maxPhotos']])">
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">Gambar <span class="font-normal normal-case opacity-70">(pilihan)</span></span>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
                </label>
            </div>

            <button type="submit" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 sm:self-start">Tambah review</button>
        </form>
    </details>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.reviews.data', ['filter' => $filter?->value]),
            'columns' => $columns,
            'searchPlaceholder' => 'Cari ulasan, penulis atau vendor…',
            'emptyTitle' => 'Tiada review sepadan',
            'emptyMessage' => 'Cuba penapis lain, atau kosongkan carian.',
            'csrf' => csrf_token(),
            'rowAction' => ['inline' => true],
        ])"
    ></div>
</x-layouts.admin>
