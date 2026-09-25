{{-- This vendor's exceptions to their plan. "Ikut pelan" shows what the plan
     gives today, so an admin can see the effect before overriding it. --}}
@php
    $planSettings = app(App\Support\VendorFeatureSettings::class);
    $plan = $vendor->featurePlan();
@endphp
<section class="mt-8 flex flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-6">
    <div class="flex flex-wrap items-baseline justify-between gap-3">
        <h2 class="font-display text-xl font-semibold">{{ __('pages.vendor_features.vendor_heading') }}</h2>
        <p class="text-sm text-ink-muted">{{ __('pages.vendor_features.vendor_plan', ['plan' => __("pages.vendor_features.plan_{$plan}")]) }} · <a href="{{ route('admin.vendor-features.index') }}" class="text-brand-700 underline underline-offset-4">{{ __('pages.vendor_features.edit_plans') }}</a></p>
    </div>

    <form method="POST" action="{{ route('admin.vendors.features', $vendor) }}" class="flex flex-col gap-4">
        @csrf
        @method('PUT')
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (App\Enums\VendorFeature::cases() as $feature)
                @php
                    $override = $vendor->featureOverride($feature);
                    $choice = match ($override) { true => 'open', false => 'closed', null => 'plan' };
                    $fromPlan = $planSettings->allows($plan, $feature) ? __('pages.vendor_features.open') : __('pages.vendor_features.closed');
                @endphp
                <x-form.select :label="$feature->label()" name="features[{{ $feature->value }}]">
                    <option value="plan" @selected($choice === 'plan')>{{ __('pages.vendor_features.follow_plan', ['state' => $fromPlan]) }}</option>
                    <option value="open" @selected($choice === 'open')>{{ __('pages.vendor_features.always_open_option') }}</option>
                    <option value="closed" @selected($choice === 'closed')>{{ __('pages.vendor_features.always_closed_option') }}</option>
                </x-form.select>
            @endforeach
        </div>
        <div>
            <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.vendor_features.save_vendor') }}</button>
        </div>
    </form>
</section>
