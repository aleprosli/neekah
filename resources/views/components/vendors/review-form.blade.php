@props(['vendor'])

@php
    $isOwnProfile = auth()->id() === $vendor->user_id;
    $errors = $errors->review;
@endphp

<div class="min-w-0 rounded-2xl border border-line bg-surface-muted/40 p-4 sm:p-6">
    @if (session('reviewStatus'))
        <p class="mb-4 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('reviewStatus') }}</p>
    @endif

    @if ($isOwnProfile)
        <p class="text-sm text-ink-muted">{{ __('pages.review_form.ini_profil_anda_sendiri_jadi') }}</p>
    @else
        <h3 class="font-display text-lg font-semibold">Tulis review untuk {{ $vendor->name }}</h3>
        <p class="mt-1 text-sm text-ink-muted">{{ __('pages.review_form.review_anda_terus_dipaparkan_ia') }}</p>

        <form method="POST" action="{{ route('vendors.reviews.store', $vendor) }}" enctype="multipart/form-data" class="mt-5 flex flex-col gap-5">
            @csrf

            @if ($errors->any())
                <ul class="flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-xs text-brand-800">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            @endif

            <fieldset class="min-w-0">
                <legend class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.review_form.berapa_bintang') }}</legend>
                {{-- Radios in reverse DOM order inside a reversed flex row, so
                     a checked star lights itself and every star drawn to its
                     left through a plain sibling selector. No JavaScript, which
                     means it still works on the markup a crawler or a visitor
                     without scripts receives. --}}
                <div class="mt-1 flex flex-row-reverse justify-end">
                    @foreach ([5, 4, 3, 2, 1] as $value)
                        <input
                            type="radio"
                            id="review-star-{{ $vendor->id }}-{{ $value }}"
                            name="rating"
                            value="{{ $value }}"
                            class="peer sr-only"
                            required
                            @checked((int) old('rating') === $value)
                        >
                        <label
                            for="review-star-{{ $vendor->id }}-{{ $value }}"
                            title="{{ $value }} bintang"
                            class="cursor-pointer px-1 py-1.5 text-3xl leading-none text-line transition peer-checked:text-gold-500 peer-focus-visible:outline peer-focus-visible:outline-brand-600"
                        >
                            <span aria-hidden="true">★</span>
                            <span class="sr-only">{{ $value }} bintang</span>
                        </label>
                    @endforeach
                </div>
            </fieldset>

            <label class="flex min-w-0 flex-col gap-1">
                <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.review_form.ulasan_anda') }}</span>
                <textarea name="comment" rows="4" required minlength="10" maxlength="1000" :placeholder="__('pages.review_form.bagaimana_pengalaman_anda_dengan_vendor')" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">{{ old('comment') }}</textarea>
            </label>

            @guest
                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <label class="flex min-w-0 flex-col gap-1">
                        <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.review_form.nama_anda') }}</span>
                        <input type="text" name="author_name" value="{{ old('author_name') }}" required minlength="2" maxlength="80" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                    </label>
                    <label class="flex min-w-0 flex-col gap-1">
                        <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.review_form.emel') }}<span class="font-normal normal-case opacity-70">(pilihan)</span></span>
                        <input type="email" name="author_email" value="{{ old('author_email') }}" maxlength="255" class="w-full rounded-xl border border-line bg-surface px-4 py-3 text-sm focus:border-brand-400 focus:outline-none">
                        <span class="text-xs text-ink-muted">{{ __('pages.review_form.tidak_dipaparkan_hanya_untuk_kami') }}</span>
                    </label>
                </div>
            @endguest

            {{-- resources/js/components/ui/UiPhotoPicker.vue, with a plain file
                 input inside as the fallback it replaces once mounted. --}}
            <div data-vue="ui-photo-picker" data-props="@vueProps(['name' => 'photos[]', 'max' => App\Models\Review::MAX_PHOTOS])">
                <label class="flex min-w-0 flex-col gap-1">
                    <span class="text-xs font-semibold tracking-wide uppercase">{{ __('pages.review_form.gambar') }}<span class="font-normal normal-case opacity-70">(pilihan, sehingga {{ App\Models\Review::MAX_PHOTOS }})</span></span>
                    <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
                </label>
            </div>

            <x-turnstile />

            <button type="submit" class="rounded-full bg-brand-600 px-6 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:self-start">{{ __('pages.review_form.hantar_review') }}</button>

            @guest
                <p class="text-xs text-ink-muted">{{ __('pages.review_form.anda_boleh_hantar_tanpa_akaun') }}<a href="{{ route('login') }}" class="font-medium text-ink underline underline-offset-4">{{ __('pages.review_form.log_masuk') }}</a> jika mahu review ini terikat pada akaun anda.
                </p>
            @endguest
        </form>
    @endif
</div>
