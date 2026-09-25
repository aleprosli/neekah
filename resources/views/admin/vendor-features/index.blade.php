{{-- What each plan opens in the vendor area. A list, not a table element: every
     application table goes through DataTable (ResponsiveTablesTest), and a
     grid of checkboxes also folds better on a phone. --}}
<x-layouts.admin :title="__('pages.vendor_features.title')" :heading="__('pages.vendor_features.title')" :subheading="__('pages.vendor_features.subheading')">
    <form method="POST" action="{{ route('admin.vendor-features.update') }}" class="flex flex-col gap-5">
        @csrf
        @method('PUT')

        <section class="overflow-hidden rounded-2xl border border-line bg-surface-raised">
            <div class="hidden grid-cols-[minmax(0,1fr)_120px_120px] gap-4 border-b border-line bg-surface-muted px-5 py-3 text-xs font-semibold text-ink-muted uppercase sm:grid">
                <span>{{ __('pages.vendor_features.feature') }}</span>
                @foreach ($plans as $plan)
                    <span class="text-center">{{ __("pages.vendor_features.plan_{$plan}") }}</span>
                @endforeach
            </div>

            <ul class="divide-y divide-line">
                @foreach ($features as $feature)
                    <li class="grid gap-3 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_120px_120px] sm:items-center sm:gap-4">
                        <div class="flex min-w-0 items-start gap-3">
                            <span class="mt-0.5 text-brand-600"><x-nav-icon :name="$feature->icon()" /></span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold">{{ $feature->label() }}</p>
                                <p class="text-xs text-ink-muted">{{ $feature->description() }}</p>
                            </div>
                        </div>
                        <div class="flex gap-6 pl-8 sm:contents">
                            @foreach ($plans as $plan)
                                <label class="flex items-center gap-2 text-sm sm:justify-center">
                                    <input type="hidden" name="features[{{ $plan }}][{{ $feature->value }}]" value="0">
                                    <input type="checkbox" name="features[{{ $plan }}][{{ $feature->value }}]" value="1" @checked($settings->allows($plan, $feature)) class="size-4 accent-brand-600">
                                    <span class="sm:sr-only">{{ __("pages.vendor_features.plan_{$plan}") }}</span>
                                </label>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>

        <p class="text-sm text-ink-muted">
            {{ __('pages.vendor_features.always_open') }}
            @if ($overridden > 0)
                {{ __('pages.vendor_features.overridden', ['count' => $overridden]) }}
            @endif
        </p>

        <div>
            <button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.vendor_features.save') }}</button>
        </div>
    </form>
</x-layouts.admin>
