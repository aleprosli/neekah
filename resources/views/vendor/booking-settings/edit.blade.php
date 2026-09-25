{{-- A Pro vendor's online booking. Three cards: is it live (and the one
     thing to fix if not), the rules couples book under, and where deposits
     go. The Herepay keys are never printed back, only whether they work. --}}
@php
    $input = 'rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none';
    $card = 'flex flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6';
    $weekdaysOn = $settings->weekdays();
    $exampleDeposit = $settings->depositFor($examplePrice);
@endphp

<x-layouts.vendor :title="__('pages.booking_settings.title')" :heading="__('pages.booking_settings.title')" :subheading="__('pages.booking_settings.subheading')">
    <div class="flex max-w-3xl flex-col gap-6">
        {{-- Live or not, and why. --}}
        <section @class([
            'flex flex-col gap-4 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between sm:p-6',
            'border-emerald-200 bg-emerald-50' => $state->isOpen(),
            'border-amber-200 bg-amber-50' => ! $state->isOpen(),
        ])>
            <div class="min-w-0">
                <p class="text-sm font-semibold">{{ $state->label() }}</p>
                <p class="mt-1 text-sm text-ink-muted">
                    @if ($settings->calendar_confirmed_at)
                        {{ __('pages.booking_settings.calendar_confirmed_ago', ['ago' => $settings->calendar_confirmed_at->diffForHumans(), 'days' => $freshDays]) }}
                    @else
                        {{ __('pages.booking_settings.calendar_never_confirmed', ['days' => $freshDays]) }}
                    @endif
                </p>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <form method="POST" action="{{ route('vendor.booking-settings.calendar') }}">
                    @csrf
                    <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.booking_settings.confirm_calendar') }}</button>
                </form>
                @if ($state->isOpen())
                    <a href="{{ route('vendors.show', $vendor) }}#hubungi" class="rounded-full border border-line bg-surface-raised px-5 py-2.5 text-sm font-medium transition hover:border-brand-400">{{ __('pages.booking_settings.view_public') }}</a>
                @endif
            </div>
        </section>

        {{-- The rules. --}}
        <form method="POST" action="{{ route('vendor.booking-settings.update') }}" class="{{ $card }}">
            @csrf
            @method('PUT')
            <h2 class="font-display text-lg font-semibold">{{ __('pages.booking_settings.rules_title') }}</h2>

            <label class="flex items-start gap-3">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" @checked(old('enabled', $settings->enabled)) class="mt-1 accent-brand-600">
                <span>
                    <span class="block text-sm font-medium">{{ __('pages.booking_settings.enabled') }}</span>
                    <span class="block text-xs text-ink-muted">{{ __('pages.booking_settings.enabled_help') }}</span>
                </span>
            </label>

            <div class="grid gap-4 sm:grid-cols-2">
                <x-form.select :label="__('pages.booking_settings.deposit_type')" name="deposit_type">
                    @foreach ($depositTypes as $type)
                        <option value="{{ $type->value }}" @selected(old('deposit_type', $settings->deposit_type->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </x-form.select>
                <x-form.field :label="__('pages.booking_settings.deposit_value')" name="deposit_value" type="number" step="0.01" min="1" :value="(float) $settings->deposit_value" required
                    :help="__('pages.booking_settings.deposit_example', ['price' => 'RM'.number_format($examplePrice), 'deposit' => 'RM'.number_format($exampleDeposit, 2)])" />
            </div>

            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium">{{ __('pages.booking_settings.weekdays') }}</legend>
                <div class="flex flex-wrap gap-2">
                    @foreach ($weekdays as $day)
                        <label class="relative cursor-pointer">
                            <input type="checkbox" name="available_weekdays[]" value="{{ $day }}" @checked(in_array($day, old('available_weekdays', $weekdaysOn))) class="peer sr-only">
                            <span class="block rounded-full border border-line px-4 py-2 text-sm font-medium text-ink-muted transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400">{{ now()->startOfWeek()->addDays($day - 1)->translatedFormat('l') }}</span>
                        </label>
                    @endforeach
                </div>
                <span class="text-xs text-ink-muted">{{ __('pages.booking_settings.weekdays_help') }}</span>
            </fieldset>

            <div class="grid gap-4 sm:grid-cols-3">
                <x-form.field :label="__('pages.booking_settings.max_per_day')" name="max_per_day" type="number" min="1" :max="App\Models\VendorBookingSetting::MAX_PER_DAY" :value="$settings->max_per_day" required :help="__('pages.booking_settings.max_per_day_help')" />
                <x-form.field :label="__('pages.booking_settings.min_lead_days')" name="min_lead_days" type="number" min="1" max="365" :value="$settings->min_lead_days" required :help="__('pages.booking_settings.min_lead_days_help')" />
                <x-form.field :label="__('pages.booking_settings.max_advance_months')" name="max_advance_months" type="number" min="1" max="36" :value="$settings->max_advance_months" required :help="__('pages.booking_settings.max_advance_months_help')" />
            </div>

            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ __('pages.booking_settings.deposit_terms') }}</span>
                <textarea name="deposit_terms" rows="4" maxlength="3000" placeholder="{{ __('pages.booking_settings.deposit_terms_placeholder') }}" class="{{ $input }}">{{ old('deposit_terms', $settings->deposit_terms) }}</textarea>
                <span class="text-xs text-ink-muted">{{ __('pages.booking_settings.deposit_terms_help') }}</span>
            </label>

            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ __('pages.booking_settings.manual_instructions') }}</span>
                <textarea name="manual_instructions" rows="3" maxlength="1000" placeholder="{{ __('pages.booking_settings.manual_instructions_placeholder') }}" class="{{ $input }}">{{ old('manual_instructions', $settings->manual_instructions) }}</textarea>
                <span class="text-xs text-ink-muted">{{ __('pages.booking_settings.manual_instructions_help') }}</span>
            </label>

            <div><button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.booking_settings.save') }}</button></div>
        </form>

        {{-- Google Calendar: busy days come in on their own every hour. --}}
        <section class="{{ $card }}">
            <div>
                <h2 class="font-display text-lg font-semibold">{{ __('pages.booking_settings.ical_title') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('pages.booking_settings.ical_intro') }}</p>
            </div>

            @if ($settings->ical_url)
                <div class="flex flex-col gap-3 rounded-xl bg-sky-50 p-4 text-sm">
                    <p class="font-semibold break-all text-sky-900">{{ $settings->maskedIcalUrl() }}</p>
                    <p class="text-sky-900/80">
                        @if ($settings->ical_error)
                            {{ __('pages.booking_settings.ical_last_error', ['error' => __('pages.booking_settings.ical_errors.'.$settings->ical_error)]) }}
                        @elseif ($settings->ical_synced_at)
                            {{ __('pages.booking_settings.ical_synced_ago', ['ago' => $settings->ical_synced_at->diffForHumans()]) }}
                        @endif
                    </p>
                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('vendor.booking-settings.ical.sync') }}">
                            @csrf
                            <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.booking_settings.ical_sync_now') }}</button>
                        </form>
                        <form method="POST" action="{{ route('vendor.booking-settings.ical.disconnect') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="rounded-full border border-sky-300 bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.booking_settings.ical_disconnect') }}</button>
                        </form>
                    </div>
                </div>
            @endif

            <form method="POST" action="{{ route('vendor.booking-settings.ical.connect') }}" class="flex flex-col gap-3">
                @csrf
                @method('PUT')
                <x-form.field :label="$settings->ical_url ? __('pages.booking_settings.ical_replace') : ucfirst(__('fields.ical_url'))" name="ical_url" type="url" placeholder="https://calendar.google.com/calendar/ical/…/basic.ics" :help="__('pages.booking_settings.ical_help')" required />
                <div><button type="submit" class="rounded-full border border-brand-600 px-5 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ __('pages.booking_settings.ical_connect') }}</button></div>
            </form>
        </section>

        {{-- Where the deposit goes. --}}
        <section class="{{ $card }}">
            <div>
                <h2 class="font-display text-lg font-semibold">{{ __('pages.booking_settings.herepay_title') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ __('pages.booking_settings.herepay_intro') }}</p>
            </div>

            @if ($settings->hasHerepay())
                <div class="flex flex-col gap-3 rounded-xl bg-emerald-50 p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                    <div class="min-w-0">
                        <p class="font-semibold text-emerald-900">{{ __('pages.booking_settings.herepay_connected', ['environment' => $environment ?? '—']) }}</p>
                        <p class="mt-0.5 text-emerald-900/80">
                            {{ $settings->herepay_verified_at
                                ? __('pages.booking_settings.herepay_verified', ['date' => $settings->herepay_verified_at->translatedFormat('j M Y')])
                                : __('pages.booking_settings.herepay_unverified') }}
                        </p>
                    </div>
                    <form method="POST" action="{{ route('vendor.booking-settings.herepay.disconnect') }}" class="shrink-0">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="rounded-full border border-emerald-300 bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ __('pages.booking_settings.herepay_disconnect') }}</button>
                    </form>
                </div>
            @else
                <form method="POST" action="{{ route('vendor.booking-settings.herepay.connect') }}" class="flex flex-col gap-4">
                    @csrf
                    @method('PUT')
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-form.field :label="__('fields.herepay_secret_key')" name="herepay_secret_key" type="password" autocomplete="off" required />
                        <x-form.field :label="__('fields.herepay_private_key')" name="herepay_private_key" type="password" autocomplete="off" required />
                    </div>
                    <p class="text-xs text-ink-muted">{{ __('pages.booking_settings.herepay_test_note', ['environment' => $environment ?? '—']) }}</p>
                    <div><button type="submit" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ __('pages.booking_settings.herepay_connect') }}</button></div>
                </form>
            @endif

            <p class="text-xs text-ink-muted">
                @if ($channel === App\Enums\DepositChannel::Herepay)
                    {{ __('pages.booking_settings.channel_herepay') }}
                @elseif ($channel === App\Enums\DepositChannel::Manual)
                    {{ __('pages.booking_settings.channel_manual') }}
                @else
                    {{ __('pages.booking_settings.channel_none') }}
                @endif
            </p>
        </section>
    </div>
</x-layouts.vendor>
