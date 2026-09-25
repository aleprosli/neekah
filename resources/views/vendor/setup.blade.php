{{-- The whole vendor area while the application waits for approval.

     One step is on screen at a time, so the page never becomes a long scroll
     of forms. The steps are radio buttons and the panels show through
     :has(), which needs no JavaScript and survives a page swap. Which step
     opens: the one with errors, else the one a save came back to (?langkah=),
     else the first still to do.

     Desktop: the guide sits in a sticky column on the left, the step on the
     right. Phone: the step comes first; the guide, the reasons and what opens
     after approval fold into accordions below it.

     Tailwind only sees class names written out in full, which is why the
     per-step tab and panel classes are spelled out in $tabClass/$panelClass
     rather than built from the key. --}}
@php
    $input = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm font-normal focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none';
    $file = 'w-full text-sm font-normal file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700 hover:file:bg-brand-100';
    $label = 'flex min-w-0 flex-col gap-1.5 text-sm font-medium';
    $submit = 'rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700';
    $hint = 'text-xs font-normal text-ink-muted';

    $total = $steps->count();
    $bagErrors = fn (string $bag): array => $errors->getBag($bag)->all();
    $fieldErrors = fn (array $fields): array => collect($fields)->flatMap(fn (string $field): array => $errors->get($field))->flatten()->all();
    $cardErrors = [
        'profil' => $bagErrors('setupProfile'),
        'cover' => $bagErrors('setupCover'),
        'portfolio' => $fieldErrors(['images', 'images.*', 'caption']),
        'pakej' => $fieldErrors(['name', 'price', 'features', 'image']),
    ];

    $keys = $steps->keys();
    $allDone = $doneCount === $total;
    $firstToDo = $keys->first(fn (string $key): bool => ! $steps[$key]['done']);
    $selected = $keys->first(fn (string $key): bool => $cardErrors[$key] !== [])
        ?? ($keys->contains($requestedStep) ? $requestedStep : null)
        ?? $firstToDo
        ?? 'selesai';

    $tabClass = [
        'profil' => 'group-has-[#langkah-profil:checked]/setup:border-brand-500 group-has-[#langkah-profil:checked]/setup:bg-brand-50 group-has-[#langkah-profil:checked]/setup:text-brand-800',
        'cover' => 'group-has-[#langkah-cover:checked]/setup:border-brand-500 group-has-[#langkah-cover:checked]/setup:bg-brand-50 group-has-[#langkah-cover:checked]/setup:text-brand-800',
        'portfolio' => 'group-has-[#langkah-portfolio:checked]/setup:border-brand-500 group-has-[#langkah-portfolio:checked]/setup:bg-brand-50 group-has-[#langkah-portfolio:checked]/setup:text-brand-800',
        'pakej' => 'group-has-[#langkah-pakej:checked]/setup:border-brand-500 group-has-[#langkah-pakej:checked]/setup:bg-brand-50 group-has-[#langkah-pakej:checked]/setup:text-brand-800',
    ];
    $panelClass = [
        'profil' => 'group-has-[#langkah-profil:checked]/setup:flex',
        'cover' => 'group-has-[#langkah-cover:checked]/setup:flex',
        'portfolio' => 'group-has-[#langkah-portfolio:checked]/setup:flex',
        'pakej' => 'group-has-[#langkah-pakej:checked]/setup:flex',
        'selesai' => 'group-has-[#langkah-selesai:checked]/setup:flex',
    ];
@endphp

<x-layouts.vendor :title="__('pages.vendor_setup.title')" :heading="__('pages.vendor_setup.welcome', ['name' => $vendor->name])" :subheading="__('pages.vendor_setup.subheading')">
    <div class="group/setup relative flex flex-col gap-6 lg:grid lg:grid-cols-[minmax(0,340px)_minmax(0,1fr)] lg:items-start lg:gap-8">
        @foreach ($steps as $key => $step)
            <input type="radio" name="langkah" id="langkah-{{ $key }}" value="{{ $key }}" class="sr-only" @checked($key === $selected)>
        @endforeach
        @if ($allDone)
            <input type="radio" name="langkah" id="langkah-selesai" value="selesai" class="sr-only" @checked($selected === 'selesai')>
        @endif

        {{-- The step on screen: first on a phone, on the right on a desktop. --}}
        <section class="min-w-0 rounded-2xl border border-line bg-surface-raised lg:order-2">
            <header class="border-b border-line p-4 sm:p-5">
                <div class="flex items-center justify-between gap-3">
                    <p class="text-sm font-semibold">{{ __('pages.vendor_setup.progress', ['done' => $doneCount, 'total' => $total]) }}</p>
                    <span class="text-xs text-ink-muted">{{ round($doneCount / $total * 100) }}%</span>
                </div>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-surface-muted" role="progressbar" aria-valuenow="{{ $doneCount }}" aria-valuemin="0" aria-valuemax="{{ $total }}">
                    <div class="h-full rounded-full bg-brand-600" style="width: {{ round($doneCount / $total * 100) }}%"></div>
                </div>
                @if ($doneCount === $total)
                    <p class="mt-3 text-sm text-emerald-700">{{ __('pages.vendor_setup.all_done') }}</p>
                @endif

                {{-- A row of chips that scrolls sideways on a phone, four tabs on a desktop. --}}
                <div class="no-scrollbar -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:grid sm:grid-cols-4 sm:overflow-visible sm:px-0" role="tablist">
                    @foreach ($steps as $key => $step)
                        <label for="langkah-{{ $key }}" role="tab" @class([
                            'relative flex shrink-0 cursor-pointer items-center gap-2 rounded-full border border-line px-3 py-2 text-sm font-medium text-ink-muted transition hover:border-brand-300 sm:min-w-0 sm:rounded-xl',
                            $tabClass[$key],
                        ])>
                            <span @class([
                                'flex size-6 shrink-0 items-center justify-center rounded-full text-xs font-semibold',
                                'bg-emerald-500 text-white' => $step['done'],
                                'bg-surface-muted text-ink-muted' => ! $step['done'],
                            ])>{{ $step['done'] ? '✓' : $loop->iteration }}</span>
                            <span class="whitespace-nowrap sm:truncate">{{ $step['label'] }}</span>
                            @if ($cardErrors[$key] !== [])
                                <span class="size-2 shrink-0 rounded-full bg-brand-600" aria-hidden="true"></span>
                            @endif
                        </label>
                    @endforeach
                </div>
            </header>

            @foreach ($steps as $key => $step)
                @php
                    $previous = $loop->first ? null : $keys->get($loop->index - 1);
                    $next = $keys->get($loop->index + 1);
                @endphp
                <div id="{{ $key }}" role="tabpanel" @class(['hidden min-w-0 flex-col gap-5 p-4 break-words sm:p-5', $panelClass[$key]])>
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h2 class="font-display text-lg font-semibold">{{ $step['label'] }}</h2>
                            <span @class([
                                'rounded-full px-2.5 py-0.5 text-xs font-medium',
                                'bg-emerald-50 text-emerald-700' => $step['done'],
                                'bg-amber-50 text-amber-800' => ! $step['done'],
                            ])>{{ $step['done'] ? __('pages.vendor_setup.done') : __('pages.vendor_setup.not_done') }}</span>
                        </div>
                        <p class="mt-1.5 text-sm text-ink-muted">{{ $step['why'] }}</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @foreach ($step['specs'] as $spec)
                                <span class="rounded-full bg-surface-muted px-2.5 py-0.5 text-xs text-ink-muted">{{ $spec }}</span>
                            @endforeach
                        </div>
                    </div>

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
                                    {{ ucfirst(__('fields.tagline')) }}
                                    <input type="text" name="tagline" maxlength="160" required value="{{ old('tagline', $vendor->tagline) }}" placeholder="{{ __('pages.vendor_setup.tagline_placeholder') }}" class="{{ $input }}">
                                </label>
                                <label class="{{ $label }}">
                                    {{ ucfirst(__('fields.penerangan')) }}
                                    <textarea name="description" rows="5" maxlength="2000" required placeholder="{{ __('pages.vendor_setup.description_placeholder') }}" class="{{ $input }}">{{ old('description', $vendor->description) }}</textarea>
                                </label>
                                <div><button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.save') }}</button></div>
                            </form>
                            @break

                        @case('cover')
                            <form method="POST" action="{{ route('vendor.setup.cover') }}" enctype="multipart/form-data" class="flex flex-col gap-4 sm:flex-row sm:items-start">
                                @csrf
                                @if ($vendor->cover_image)
                                    <figure class="flex shrink-0 flex-col gap-1.5 sm:w-56">
                                        <img src="{{ Illuminate\Support\Facades\Storage::disk('public')->url($vendor->cover_image) }}" alt="" class="aspect-video w-full rounded-xl object-cover">
                                        <figcaption class="{{ $hint }}">{{ __('pages.vendor_setup.current_cover') }}</figcaption>
                                    </figure>
                                @endif
                                <div class="flex min-w-0 flex-1 flex-col gap-4">
                                    <label class="{{ $label }}">
                                        {{ __('pages.vendor_setup.new_cover') }}
                                        <input type="file" name="cover_image" required accept="image/jpeg,image/png,image/webp" class="{{ $file }}">
                                        <span class="{{ $hint }}">{{ $imageHint }}</span>
                                    </label>
                                    <div><button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.upload') }}</button></div>
                                </div>
                            </form>
                            @break

                        @case('portfolio')
                            @if ($portfolio->isNotEmpty())
                                <div>
                                    <p class="mb-2 text-xs font-medium text-ink-muted">{{ __('pages.vendor_setup.current_photos', ['count' => $portfolio->count()]) }}</p>
                                    <ul class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:grid sm:grid-cols-6 sm:overflow-visible sm:px-0">
                                        @foreach ($portfolio as $item)
                                            <li class="relative w-24 shrink-0 sm:w-auto">
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
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="{{ $label }}">
                                        {{ __('pages.vendor_setup.choose_photos') }}
                                        <input type="file" name="images[]" multiple required accept="image/jpeg,image/png,image/webp" class="{{ $file }}">
                                        <span class="{{ $hint }}">{{ $imageHint }}</span>
                                    </label>
                                    <label class="{{ $label }}">
                                        {{ __('pages.vendor_setup.caption') }}
                                        <input type="text" name="caption" maxlength="160" value="{{ old('caption') }}" class="{{ $input }}">
                                    </label>
                                </div>
                                <div><button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.upload') }}</button></div>
                            </form>
                            @break

                        @case('pakej')
                            @if ($packages->isNotEmpty())
                                <div>
                                    <p class="mb-2 text-xs font-medium text-ink-muted">{{ __('pages.vendor_setup.your_packages') }}</p>
                                    <ul class="flex flex-col divide-y divide-line rounded-xl border border-line">
                                        @foreach ($packages as $package)
                                            <li class="flex items-center gap-3 px-4 py-2.5 text-sm">
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
                                <div class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_160px]">
                                    <label class="{{ $label }}">
                                        {{ __('pages.vendor_setup.package_name') }}
                                        <input type="text" name="name" maxlength="120" required value="{{ old('name') }}" class="{{ $input }}">
                                    </label>
                                    <label class="{{ $label }}">
                                        {{ __('pages.vendor_setup.package_price') }}
                                        <input type="number" name="price" min="0" step="0.01" required value="{{ old('price') }}" class="{{ $input }}">
                                    </label>
                                </div>
                                <div class="grid gap-4 sm:grid-cols-2">
                                    <label class="{{ $label }}">
                                        {{ __('pages.vendor_setup.package_features') }}
                                        <textarea name="features" rows="3" maxlength="2000" required class="{{ $input }}">{{ old('features') }}</textarea>
                                        <span class="{{ $hint }}">{{ __('pages.vendor_setup.package_features_help') }}</span>
                                    </label>
                                    <label class="{{ $label }}">
                                        {{ __('pages.vendor_setup.package_image') }}
                                        <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="{{ $file }}">
                                        <span class="{{ $hint }}">{{ $imageHint }}</span>
                                    </label>
                                </div>
                                <div><button type="submit" class="{{ $submit }}">{{ __('pages.vendor_setup.add_package') }}</button></div>
                            </form>
                            @break
                    @endswitch

                    {{-- Labels for the neighbouring radios, dressed as buttons: moving
                         between steps saves nothing, so they stay quieter than "Simpan". --}}
                    <div class="flex gap-2 border-t border-line pt-4">
                        @if ($previous)
                            <label for="langkah-{{ $previous }}" class="flex min-w-0 flex-1 cursor-pointer items-center gap-2 rounded-xl border border-line px-3 py-2.5 transition hover:border-brand-300 sm:flex-none">
                                <span class="text-ink-muted" aria-hidden="true">←</span>
                                <span class="min-w-0 leading-tight">
                                    <span class="block text-sm font-semibold">{{ __('pages.vendor_setup.previous') }}</span>
                                    <span class="block truncate text-xs text-ink-muted">{{ $steps[$previous]['label'] }}</span>
                                </span>
                            </label>
                        @endif
                        @if (! $next && $allDone)
                            <label for="langkah-selesai" class="ml-auto flex cursor-pointer items-center gap-2 rounded-xl bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-700">
                                {{ __('pages.vendor_setup.finish') }}
                                <span aria-hidden="true">✓</span>
                            </label>
                        @elseif (! $next && $firstToDo)
                            <label for="langkah-{{ $firstToDo }}" class="ml-auto flex min-w-0 flex-1 cursor-pointer items-center justify-end gap-2 rounded-xl bg-amber-50 px-3 py-2.5 text-right text-amber-900 ring-1 ring-amber-200 transition hover:bg-amber-100 sm:flex-none">
                                <span class="min-w-0 leading-tight">
                                    <span class="block text-sm font-semibold">{{ __('pages.vendor_setup.still_to_do', ['count' => $total - $doneCount]) }}</span>
                                    <span class="block truncate text-xs text-amber-800/80">{{ $steps[$firstToDo]['label'] }}</span>
                                </span>
                                <span aria-hidden="true">→</span>
                            </label>
                        @endif
                        @if ($next)
                            <label for="langkah-{{ $next }}" class="ml-auto flex min-w-0 flex-1 cursor-pointer items-center justify-end gap-2 rounded-xl bg-brand-50 px-3 py-2.5 text-right text-brand-800 ring-1 ring-brand-100 transition hover:bg-brand-100 sm:flex-none">
                                <span class="min-w-0 leading-tight">
                                    <span class="block text-sm font-semibold">{{ __('pages.vendor_setup.next') }}</span>
                                    <span class="block truncate text-xs text-brand-700/80">{{ $steps[$next]['label'] }}</span>
                                </span>
                                <span aria-hidden="true">→</span>
                            </label>
                        @endif
                    </div>
                </div>
            @endforeach

            @if ($allDone)
                <div role="tabpanel" @class(['hidden flex-col items-center gap-4 px-5 py-10 text-center sm:px-10', $panelClass['selesai']])>
                    <span class="flex size-16 items-center justify-center rounded-full bg-emerald-50 text-3xl ring-8 ring-emerald-50/50" aria-hidden="true">🎉</span>
                    <h2 class="font-display text-2xl font-semibold">{{ __('pages.vendor_setup.thanks_title', ['name' => $vendor->name]) }}</h2>
                    <p class="max-w-md text-sm text-ink-muted">{{ __('pages.vendor_setup.thanks_body') }}</p>
                    <p class="max-w-md text-sm text-ink-muted">{{ __('pages.vendor_setup.thanks_meanwhile') }}</p>
                    <label for="langkah-{{ $keys->first() }}" class="mt-2 cursor-pointer rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">{{ __('pages.vendor_setup.review_again') }}</label>
                </div>
            @endif
        </section>

        {{-- The guide: a column beside the step on a desktop, under it on a
             phone. It scrolls with the page and never on its own: an inner
             scroll hid half of an opened section and nobody could tell there
             was more. --}}
        <aside class="flex min-w-0 flex-col gap-3 lg:order-1">
            <div class="rounded-2xl border border-gold-300/60 bg-surface-raised p-5">
                <h2 class="font-display text-lg font-semibold">{{ __('pages.vendor_setup.guide_title') }}</h2>
                <ol class="mt-4 flex flex-col gap-3">
                    @foreach ([1, 2, 3] as $number)
                        <li class="flex min-w-0 gap-3">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700 ring-1 ring-brand-100">{{ $number }}</span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ __("pages.vendor_setup.guide_{$number}_title") }}</p>
                                <p class="mt-0.5 text-xs text-ink-muted">{{ __("pages.vendor_setup.guide_{$number}_body") }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            @foreach ([
                ['title' => __('pages.vendor_setup.why_title'), 'intro' => __('pages.vendor_setup.why_intro'), 'items' => array_map(fn (string $reason): string => __("pages.vendor_setup.why_{$reason}"), ['seo', 'marketing', 'referral']), 'outro' => __('pages.vendor_setup.why_community'), 'mark' => '✦'],
                ['title' => __('pages.vendor_setup.unlock_title'), 'intro' => __('pages.vendor_setup.unlock_intro'), 'items' => array_map(fn (string $feature): string => __("pages.vendor_setup.unlock_{$feature}"), ['page', 'reach', 'enquiries', 'reviews', 'ranking', 'pro']), 'outro' => __('pages.vendor_setup.unlock_note'), 'mark' => '🔒'],
            ] as $fold)
                <details class="group rounded-2xl border border-line bg-surface-raised">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-5 py-4 text-sm font-semibold [&::-webkit-details-marker]:hidden">
                        {{ $fold['title'] }}
                        <svg class="size-4 shrink-0 text-ink-muted transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </summary>
                    <div class="border-t border-line px-5 py-4 text-sm text-ink-muted">
                        <p>{{ $fold['intro'] }}</p>
                        <ul class="mt-2 flex flex-col gap-1.5">
                            @foreach ($fold['items'] as $item)
                                <li class="flex gap-2"><span class="shrink-0" aria-hidden="true">{{ $fold['mark'] }}</span><span>{{ $item }}</span></li>
                            @endforeach
                        </ul>
                        <p class="mt-2">{{ $fold['outro'] }}</p>
                    </div>
                </details>
            @endforeach

            @if ($support !== [])
                @php
                    $row = 'flex min-w-0 items-center gap-3 rounded-xl border border-line bg-surface-raised px-3 py-2.5 transition hover:border-brand-400';
                    $icon = 'flex size-8 shrink-0 items-center justify-center rounded-full';
                @endphp
                <div class="rounded-2xl bg-brand-50/70 p-5 text-sm">
                    <p class="text-ink-muted">{{ __('pages.vendor_setup.help_intro') }}</p>
                    <div class="mt-3 flex flex-col gap-2">
                        @isset($support['whatsapp'])
                            <a href="{{ $support['whatsapp'] }}" target="_blank" rel="noopener" class="{{ $row }} border-emerald-200">
                                <span class="{{ $icon }} bg-emerald-600 text-white">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm5.3 14.2c-.2.6-1.3 1.2-1.8 1.2-.5.1-1 .2-3.3-.7-2.8-1.1-4.5-3.9-4.7-4.1-.1-.2-1.1-1.5-1.1-2.8s.7-2 1-2.3c.2-.3.5-.3.7-.3h.5c.2 0 .4 0 .6.5l.8 2c.1.2.1.3 0 .5l-.3.5-.4.5c-.1.1-.3.3-.1.6.2.3.8 1.3 1.7 2.1 1.1 1 2.1 1.3 2.4 1.5.3.1.5.1.6-.1l.9-1.1c.2-.3.4-.2.7-.1l1.9.9c.3.1.5.2.5.3.1.2.1.6-.1 1.2Z"/></svg>
                                </span>
                                <span class="min-w-0 flex-1 font-semibold">{{ __('pages.vendor_setup.help_whatsapp') }}</span>
                                <span class="text-ink-muted" aria-hidden="true">→</span>
                            </a>
                        @endisset
                        @isset($support['phone'])
                            <a href="{{ $support['phone']['url'] }}" class="{{ $row }}">
                                <span class="{{ $icon }} bg-brand-100 text-brand-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1.9.4 1.8.7 2.7a2 2 0 0 1-.5 2.1L8 9.8a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.7.7a2 2 0 0 1 1.7 2Z"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold">{{ __('pages.vendor_setup.help_call_label') }}</span>
                                    <span class="block truncate text-xs text-ink-muted">{{ $support['phone']['label'] }}</span>
                                </span>
                            </a>
                        @endisset
                        @isset($support['email'])
                            <a href="mailto:{{ $support['email'] }}" class="{{ $row }}">
                                <span class="{{ $icon }} bg-brand-100 text-brand-700">
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block font-semibold">{{ __('pages.vendor_setup.help_email_label') }}</span>
                                    <span class="block truncate text-xs text-ink-muted">{{ $support['email'] }}</span>
                                </span>
                            </a>
                        @endisset
                    </div>
                </div>
            @endif
        </aside>
    </div>
</x-layouts.vendor>
