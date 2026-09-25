{{-- The whole vendor area while the application waits for approval: a short
     guide to what happens next, then one card per onboarding step. Each card
     saves in place and comes back here, so a new vendor never has to find the
     full editor pages (they open, with the sidebar, once approved). Done cards
     fold shut; the rest stay open. --}}
@php
    $input = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none';
    $file = 'w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100';
    $label = 'flex flex-col gap-1.5 text-sm font-medium';
    $submit = 'self-start rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700';
    $total = $steps->count();
    $bagErrors = fn (string $bag): array => $errors->getBag($bag)->all();
    $fieldErrors = fn (array $fields): array => collect($fields)->flatMap(fn (string $field): array => $errors->get($field))->flatten()->all();
    $cardErrors = [
        'profil' => $bagErrors('setupProfile'),
        'cover' => $bagErrors('setupCover'),
        'portfolio' => $fieldErrors(['images', 'images.*', 'caption']),
        'pakej' => $fieldErrors(['name', 'price', 'features', 'image']),
        'harga' => $bagErrors('setupPrice'),
    ];
@endphp

<x-layouts.vendor :title="__('pages.vendor_setup.title')" :heading="__('pages.vendor_setup.welcome', ['name' => $vendor->name])" :subheading="__('pages.vendor_setup.subheading')">
    <section class="mb-8 rounded-2xl border border-gold-300/60 bg-surface-raised p-5 sm:p-6">
        <h2 class="font-display text-lg font-semibold">{{ __('pages.vendor_setup.guide_title') }}</h2>

        <ol class="mt-4 grid gap-4 md:grid-cols-3">
            @foreach ([1, 2, 3] as $number)
                <li class="flex min-w-0 gap-3">
                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-brand-50 text-sm font-semibold text-brand-700 ring-1 ring-brand-100">{{ $number }}</span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold">{{ __("pages.vendor_setup.guide_{$number}_title") }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ __("pages.vendor_setup.guide_{$number}_body") }}</p>
                    </div>
                </li>
            @endforeach
        </ol>

        <div class="mt-6">
            <p class="text-sm font-medium">{{ __('pages.vendor_setup.progress', ['done' => $doneCount, 'total' => $total]) }}</p>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuenow="{{ $doneCount }}" aria-valuemin="0" aria-valuemax="{{ $total }}">
                <div class="h-full rounded-full bg-brand-600" style="width: {{ round($doneCount / $total * 100) }}%"></div>
            </div>
            @if ($doneCount === $total)
                <p class="mt-3 text-sm text-emerald-700">{{ __('pages.vendor_setup.all_done') }}</p>
            @endif
        </div>
    </section>

    <div class="flex flex-col gap-4">
        @foreach ($steps as $key => $step)
            <details id="{{ $key }}" class="group scroll-mt-24 rounded-2xl border border-line bg-surface-raised" @if (! $step['done'] || $cardErrors[$key] !== []) open @endif>
                <summary class="flex cursor-pointer list-none items-center gap-4 p-5 [&::-webkit-details-marker]:hidden">
                    <span @class([
                        'flex size-8 shrink-0 items-center justify-center rounded-full text-sm font-semibold',
                        'bg-emerald-500 text-white' => $step['done'],
                        'border border-line text-ink-muted' => ! $step['done'],
                    ])>{{ $step['done'] ? '✓' : $loop->iteration }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">{{ $step['label'] }}</span>
                        <span class="mt-0.5 block text-xs text-ink-muted">{{ implode(' · ', $step['specs']) }}</span>
                    </span>
                    <span @class([
                        'shrink-0 rounded-full px-2.5 py-1 text-xs font-medium',
                        'bg-emerald-50 text-emerald-700' => $step['done'],
                        'bg-amber-50 text-amber-800' => ! $step['done'],
                    ])>{{ $step['done'] ? __('pages.vendor_setup.done') : __('pages.vendor_setup.not_done') }}</span>
                    <svg class="size-4 shrink-0 text-ink-muted transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                </summary>

                <div class="flex min-w-0 flex-col gap-5 border-t border-line p-5 break-words">
                    <p class="text-sm text-ink-muted">{{ $step['why'] }}</p>

                    @if ($cardErrors[$key] !== [])
                        <ul class="flex flex-col gap-1 rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm text-brand-800">
                            @foreach ($cardErrors[$key] as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @switch($key)
                        @case('profil')
                            <form method="POST" action="{{ route('vendor.setup.profile') }}" class="flex flex-col gap-4">
                                @csrf
                                @method('PUT')
                                <label class="{{ $label }}">
                                    {{ __('fields.tagline') }}
                                    <input type="text" name="tagline" maxlength="160" required value="{{ old('tagline', $vendor->tagline) }}" placeholder="{{ __('pages.vendor_setup.tagline_placeholder') }}" class="{{ $input }} font-normal">
                                </label>
                                <label class="{{ $label }}">
                                    {{ ucfirst(__('fields.penerangan')) }}
                                    <textarea name="description" rows="5" maxlength="2000" required placeholder="{{ __('pages.vendor_setup.description_placeholder') }}" class="{{ $input }} font-normal">{{ old('description', $vendor->description) }}</textarea>
                                </label>
                                <button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.save') }}</button>
                            </form>
                            @break

                        @case('cover')
                            @if ($vendor->cover_image)
                                <figure class="flex flex-col gap-2">
                                    <figcaption class="text-xs font-medium text-ink-muted">{{ __('pages.vendor_setup.current_cover') }}</figcaption>
                                    <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($vendor->cover_image) }}" alt="" class="aspect-video w-full max-w-md rounded-xl object-cover">
                                </figure>
                            @endif
                            <form method="POST" action="{{ route('vendor.setup.cover') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                                @csrf
                                <label class="{{ $label }}">
                                    {{ __('pages.vendor_setup.new_cover') }}
                                    <input type="file" name="cover_image" required accept="image/jpeg,image/png,image/webp" class="{{ $file }} font-normal">
                                    <span class="text-xs font-normal text-ink-muted">{{ $imageHint }}</span>
                                </label>
                                <button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.upload') }}</button>
                            </form>
                            @break

                        @case('portfolio')
                            @if ($portfolio->isNotEmpty())
                                <div>
                                    <p class="mb-2 text-xs font-medium text-ink-muted">{{ __('pages.vendor_setup.current_photos', ['count' => $portfolio->count()]) }}</p>
                                    <ul class="grid grid-cols-3 gap-2 sm:grid-cols-5">
                                        @foreach ($portfolio as $item)
                                            <li class="relative">
                                                <img src="{{ $item->thumbnailUrl() }}" alt="{{ $item->caption }}" class="aspect-square w-full rounded-lg object-cover">
                                                <form method="POST" action="{{ route('vendor.portfolio.destroy', $item) }}" class="absolute top-1 right-1">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-full bg-surface-raised/90 px-2 py-0.5 text-xs font-medium text-ink shadow-sm hover:text-brand-700">{{ __('pages.vendor_setup.delete') }}</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('vendor.portfolio.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                                @csrf
                                <label class="{{ $label }}">
                                    {{ __('pages.vendor_setup.choose_photos') }}
                                    <input type="file" name="images[]" multiple required accept="image/jpeg,image/png,image/webp" class="{{ $file }} font-normal">
                                    <span class="text-xs font-normal text-ink-muted">{{ $imageHint }}</span>
                                </label>
                                <label class="{{ $label }}">
                                    {{ __('pages.vendor_setup.caption') }}
                                    <input type="text" name="caption" maxlength="160" value="{{ old('caption') }}" class="{{ $input }} font-normal">
                                </label>
                                <button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.upload') }}</button>
                            </form>
                            @break

                        @case('pakej')
                            @if ($packages->isNotEmpty())
                                <div>
                                    <p class="mb-2 text-xs font-medium text-ink-muted">{{ __('pages.vendor_setup.your_packages') }}</p>
                                    <ul class="flex flex-col divide-y divide-line rounded-xl border border-line">
                                        @foreach ($packages as $package)
                                            <li class="flex items-center gap-3 px-4 py-3 text-sm">
                                                <span class="min-w-0 flex-1 truncate font-medium">{{ $package->name }}</span>
                                                <span class="shrink-0 text-ink-muted">RM{{ number_format((float) $package->price, 2) }}</span>
                                                <form method="POST" action="{{ route('vendor.packages.destroy', $package) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs font-medium text-ink-muted hover:text-brand-700">{{ __('pages.vendor_setup.delete') }}</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="{{ route('vendor.packages.store') }}" enctype="multipart/form-data" class="flex flex-col gap-4">
                                @csrf
                                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_180px]">
                                    <label class="{{ $label }} min-w-0">
                                        {{ __('pages.vendor_setup.package_name') }}
                                        <input type="text" name="name" maxlength="120" required value="{{ old('name') }}" class="{{ $input }} font-normal">
                                    </label>
                                    <label class="{{ $label }} min-w-0">
                                        {{ __('pages.vendor_setup.package_price') }}
                                        <input type="number" name="price" min="0" step="0.01" required value="{{ old('price') }}" class="{{ $input }} font-normal">
                                    </label>
                                </div>
                                <label class="{{ $label }}">
                                    {{ __('pages.vendor_setup.package_features') }}
                                    <textarea name="features" rows="4" maxlength="2000" required class="{{ $input }} font-normal">{{ old('features') }}</textarea>
                                    <span class="text-xs font-normal text-ink-muted">{{ __('pages.vendor_setup.package_features_help') }}</span>
                                </label>
                                <label class="{{ $label }}">
                                    {{ __('pages.vendor_setup.package_image') }}
                                    <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $file }} font-normal">
                                    <span class="text-xs font-normal text-ink-muted">{{ $imageHint }}</span>
                                </label>
                                <button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.add_package') }}</button>
                            </form>
                            @break

                        @case('harga')
                            <form method="POST" action="{{ route('vendor.setup.price') }}" class="flex flex-col gap-4">
                                @csrf
                                @method('PUT')
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="{{ $label }} min-w-0">
                                        {{ __('pages.vendor_setup.price_from') }}
                                        <input type="number" name="price_from" min="0.01" step="0.01" required value="{{ old('price_from', (float) $vendor->price_from > 0 ? $vendor->price_from : null) }}" class="{{ $input }} font-normal">
                                    </label>
                                    <label class="{{ $label }} min-w-0">
                                        {{ __('pages.vendor_setup.price_unit') }}
                                        <select name="price_unit" class="{{ $input }} font-normal">
                                            @foreach ($priceUnits as $unit)
                                                <option value="{{ $unit->value }}" @selected(old('price_unit', $vendor->price_unit->value) === $unit->value)>{{ __('props.vendor.setiap').$unit->label() }}</option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                                <button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.save') }}</button>
                            </form>
                            @break

                    @endswitch
                </div>
            </details>
        @endforeach
    </div>
</x-layouts.vendor>
