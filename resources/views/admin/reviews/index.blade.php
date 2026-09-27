<x-layouts.admin :title="__('pages.admin_reviews.review')" :heading="__('pages.admin_reviews.review_2')" :subheading="__('pages.admin_reviews.semua_review_pada_setiap_profil')">
    {{-- Carrying over a review the vendor already had elsewhere. --}}
    <details class="mb-6 min-w-0 rounded-2xl border border-line bg-surface-muted/40 p-4 sm:p-6">
        <summary class="cursor-pointer text-sm font-semibold">{{ __('pages.admin_reviews.tambah_review_secara_manual') }}</summary>

        <p class="mt-2 text-sm text-ink-muted">{{ __('pages.admin_reviews.untuk_memindahkan_review_sedia_ada') }}</p>

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
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.admin_reviews.vendor') }}</span>
                    <select name="vendor_id" required class="nk-select w-full rounded-xl border border-line bg-surface px-4 py-3 pr-10 text-sm focus:border-brand-400 focus:outline-none">
                        <option value="">{{ __('pages.admin_reviews.pilih_vendor') }}</option>
                        @foreach ($props['vendors'] as $option)
                            <option value="{{ $option['id'] }}" @selected((int) old('vendor_id') === $option['id'])>{{ $option['name'] }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.admin_reviews.nama_penulis') }}</span>
                    <input type="text" name="author_name" value="{{ old('author_name') }}" required minlength="2" maxlength="80" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>

                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.admin_reviews.emel_penulis') }} <span class="font-normal normal-case opacity-70">{{ __('ui.common.optional') }}</span></span>
                    <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="255" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>

                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.admin_reviews.tarikh_asal') }} <span class="font-normal normal-case opacity-70">{{ __('ui.common.optional') }}</span></span>
                    <input type="date" name="written_on" value="{{ old('written_on') }}" max="{{ now()->toDateString() }}" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>

            <fieldset class="min-w-0">
                <legend class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.admin_reviews.berapa_bintang') }}</legend>
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
                <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.admin_reviews.ulasan') }}</span>
                <textarea name="comment" rows="3" required minlength="10" maxlength="1000" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">{{ old('comment') }}</textarea>
            </label>

            <div data-vue="ui-photo-picker" data-props="@vueProps(['name' => 'photos[]', 'max' => $props['maxPhotos']])">
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.admin_reviews.gambar') }} <span class="font-normal normal-case opacity-70">{{ __('ui.common.optional') }}</span></span>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
                </label>
            </div>

            <button type="submit" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 sm:self-start">{{ __('pages.admin_reviews.tambah_review') }}</button>
        </form>
    </details>

    {{-- resources/js/components/ui/DataTable.vue --}}
    <div
        data-vue="data-table"
        data-props="@vueProps([
            'dataUrl' => route('admin.reviews.data'),
            'columns' => $columns,
            'filters' => $filters,
            'searchPlaceholder' => __('pages.tables.cari_ulasan_penulis_atau_vendor'),
            'emptyTitle' => __('pages.tables.tiada_review_sepadan'),
            'emptyMessage' => __('pages.tables.cuba_penapis_lain_atau_kosongkan'),
            'csrf' => csrf_token(),
            'rowAction' => ['inline' => true],
        ])"
    ></div>
</x-layouts.admin>
