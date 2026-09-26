<x-layouts.vendor :title="__('pages.reviews.review')" :heading="__('pages.reviews.review_2')" :subheading="__('pages.reviews.apa_yang_pelanggan_tulis', ['vendor' => $vendor->name])">
    @if (session('status'))
        <p class="mb-6 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('status') }}</p>
    @endif

    <p class="mb-6 max-w-2xl text-sm text-ink-muted">{{ __('pages.reviews.anda_boleh_menjawab_mana_mana') }}</p>

    {{-- Carrying over reviews the vendor already has somewhere else. --}}
    <details class="mb-6 min-w-0 rounded-2xl border border-line bg-surface-muted/40 p-4 sm:p-6" @if ($errors->addReview->any()) open @endif>
        <summary class="cursor-pointer text-sm font-semibold">{{ __('pages.reviews.tambah_review_dari_tempat_lain') }}</summary>

        <p class="mt-2 max-w-2xl text-sm text-ink-muted">{{ __('pages.reviews.untuk_review_sebenar_yang_anda') }} <span class="font-medium text-ink">&ldquo;{{ __('enums.review_source.vendor_added') }}&rdquo;</span>{{ __('pages.reviews.ditambah_oleh_vendor_penjelasan') }}
        </p>

        <form method="POST" action="{{ route('vendor.reviews.store') }}" enctype="multipart/form-data" class="mt-4 flex flex-col gap-4">
            @csrf

            @if ($errors->addReview->any())
                <ul class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">
                    @foreach ($errors->addReview->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="grid min-w-0 gap-4 sm:grid-cols-3">
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.reviews.nama_pelanggan') }}</span>
                    <input type="text" name="author_name" value="{{ old('author_name') }}" required minlength="2" maxlength="80" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.reviews.emel') }} <span class="font-normal normal-case opacity-70">{{ __('ui.common.optional') }}</span></span>
                    <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="255" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.reviews.tarikh_asal') }} <span class="font-normal normal-case opacity-70">{{ __('ui.common.optional') }}</span></span>
                    <input type="date" name="written_on" value="{{ old('written_on') }}" max="{{ now()->toDateString() }}" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                </label>
            </div>

            <fieldset class="min-w-0">
                <legend class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.reviews.berapa_bintang') }}</legend>
                <div class="mt-1 flex flex-row-reverse justify-end">
                    @foreach ([5, 4, 3, 2, 1] as $value)
                        <input type="radio" id="vendor-review-star-{{ $value }}" name="rating" value="{{ $value }}" class="peer sr-only" required @checked((int) old('rating') === $value)>
                        <label for="vendor-review-star-{{ $value }}" class="cursor-pointer px-1 py-1.5 text-3xl leading-none text-line transition peer-checked:text-gold-500 peer-focus-visible:outline peer-focus-visible:outline-brand-600">
                            <span aria-hidden="true">★</span>
                            <span class="sr-only">{{ $value }} bintang</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="flex min-w-0 flex-col gap-1">
                <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.reviews.ulasan') }}</span>
                <textarea name="comment" rows="3" required minlength="10" maxlength="1000" :placeholder="__('pages.reviews.salin_apa_yang_pelanggan_tulis')" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">{{ old('comment') }}</textarea>
            </label>

            <div data-vue="ui-photo-picker" data-props="@vueProps(['name' => 'photos[]', 'max' => App\Models\Review::MAX_PHOTOS])">
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.reviews.gambar') }} <span class="font-normal normal-case opacity-70">{{ __('ui.common.optional') }}</span></span>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
                </label>
            </div>

            <button type="submit" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 sm:self-start">{{ __('pages.reviews.tambah_review') }}</button>
        </form>
    </details>

    @if ($reviews->isEmpty())
        <p class="rounded-2xl border border-dashed border-line p-8 text-center text-sm text-ink-muted">{{ __('pages.reviews.belum_ada_review') }}</p>
    @else
        <ul class="flex flex-col gap-4">
            @foreach ($reviews as $review)
                <li @class(['min-w-0 rounded-2xl border border-line bg-surface-raised p-4 break-words sm:p-6', 'opacity-60' => $review->isHidden()])>
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <p class="font-semibold">{{ $review->authorName() }}</p>
                            <p class="text-xs text-ink-muted">
                                {{ $review->created_at->translatedFormat('j F Y') }} · {{ $review->sourceLabel() }}
                            </p>
                        </div>
                        <p class="text-sm text-gold-500">{{ str_repeat('★', $review->rating) }}<span class="text-line">{{ str_repeat('★', 5 - $review->rating) }}</span></p>
                    </div>

                    <p class="mt-3 text-sm leading-relaxed">{{ $review->comment }}</p>

                    @if ($review->photos->isNotEmpty())
                        <ul class="mt-3 flex flex-wrap gap-2">
                            @foreach ($review->photos as $photo)
                                <li>
                                    <a href="{{ $photo->url() }}" target="_blank" rel="noopener">
                                        <img src="{{ $photo->thumbnailUrl() }}" alt="" loading="lazy" class="size-20 rounded-lg object-cover">
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if ($review->isHidden())
                        <p class="mt-3 rounded-xl bg-surface-muted px-3 py-2 text-xs text-ink-muted">
                            Review ini sudah ditarik oleh admin{{ $review->hidden_reason ? ' — '.$review->hidden_reason : '' }}. Ia tidak dipaparkan pada profil anda.
                        </p>
                    @endif

                    @if ($review->hasReply())
                        <div class="mt-3 min-w-0 rounded-xl border-l-2 border-brand-200 bg-surface-muted/60 px-3 py-2">
                            <p class="text-xs font-semibold">{{ __('pages.reviews.jawapan_anda') }}</p>
                            <p class="mt-1 text-sm leading-relaxed">{{ $review->reply }}</p>
                        </div>
                    @endif

                    @if ($review->isReported())
                        <p class="mt-3 rounded-xl bg-amber-50 px-3 py-2 text-xs text-amber-800">
                            Dilaporkan pada {{ $review->reported_at->translatedFormat('j F Y') }}. Admin akan memeriksanya.
                        </p>
                    @endif

                    @can('deleteOwnAddition', $review)
                        {{-- resources/js/components/ui/UiConfirm.vue --}}
                        <div class="mt-3" data-vue="ui-confirm" data-props="@vueProps([
                            'action' => route('vendor.reviews.destroy', $review),
                            'method' => 'DELETE',
                            'csrf' => csrf_token(),
                            'tone' => 'danger',
                            'title' => __('flash.confirm.delete_review_title', ['author' => $review->author_name]),
                            'message' => __('flash.confirm.delete_review_message'),
                            'confirmLabel' => __('flash.confirm.yes_delete'),
                            'label' => __('flash.confirm.delete_review_label'),
                            'triggerClass' => 'rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400',
                        ])"></div>
                    @endcan

                    @can('reply', $review)
                        <div class="mt-4 flex flex-col gap-2 border-t border-line pt-4 sm:flex-row sm:gap-3">
                            <details class="min-w-0 flex-1">
                                <summary class="cursor-pointer text-xs font-semibold">{{ $review->hasReply() ? 'Kemas kini jawapan' : 'Jawab review ini' }}</summary>
                                <form method="POST" action="{{ route('vendor.reviews.reply', $review) }}" class="mt-2 flex flex-col gap-2">
                                    @csrf
                                    <textarea name="reply" rows="3" required minlength="5" maxlength="1000" :placeholder="__('pages.reviews.jawapan_anda_dipaparkan_di_bawah')" class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">{{ old('reply', $review->reply) }}</textarea>
                                    <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-xs font-semibold text-white transition hover:bg-brand-700 sm:self-start">{{ __('pages.reviews.hantar_jawapan') }}</button>
                                </form>
                            </details>

                            @can('report', $review)
                                <details class="min-w-0 flex-1">
                                    <summary class="cursor-pointer text-xs font-semibold text-ink-muted">{{ __('pages.reviews.laporkan_kepada_admin') }}</summary>
                                    <form method="POST" action="{{ route('vendor.reviews.report', $review) }}" class="mt-2 flex flex-col gap-2">
                                        @csrf
                                        <textarea name="reason" rows="3" required minlength="10" maxlength="1000" :placeholder="__('pages.reviews.terangkan_kenapa_review_ini_tidak')" class="w-full rounded-xl border border-line bg-surface px-3 py-2 text-sm focus:border-brand-400 focus:outline-none">{{ old('reason') }}</textarea>
                                        <button type="submit" class="rounded-full border border-line px-4 py-2 text-xs font-semibold transition hover:border-brand-400 sm:self-start">{{ __('pages.reviews.hantar_laporan') }}</button>
                                    </form>
                                </details>
                            @endcan
                        </div>
                    @endcan
                </li>
            @endforeach
        </ul>

        <div class="mt-6">{{ $reviews->onEachSide(1)->links() }}</div>
    @endif
</x-layouts.vendor>
