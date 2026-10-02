{{-- The couple's home, kept to what moves them forward. With a wedding the
     page has no heading of its own: the names, the date and the countdown
     are one card at the top. --}}
<x-layouts.customer :title="__('pages.dash.majlis_saya')" :heading="$wedding ? null : __('pages.dash.majlis_saya')" :subheading="$wedding ? null : __('pages.dash.cipta_wedding_project_sub')">
    @if ($wedding)
        {{-- resources/js/components/customer/WeddingCountdown.vue; the names and
             the day count are what show before it mounts. --}}
        <div class="mb-6" data-vue="wedding-countdown" data-props="@vueProps([
            'target' => $wedding->startsAt()->toIso8601String(),
            'progress' => $wedding->planningProgress(),
            'title' => $wedding->title,
            'detail' => $wedding->event_date->translatedFormat('l, j F Y').' · '.$wedding->city.', '.$wedding->state,
            'links' => [
                ['label' => $wedding->site?->is_published ? __('pages.dash.kad_digital_saya') : __('pages.dash.buat_kad_digital'), 'url' => route('site.edit'), 'primary' => true],
                ['label' => __('pages.dash.edit_majlis'), 'url' => route('weddings.edit', $wedding), 'primary' => false],
            ],
        ])">
            <div class="rounded-[1.75rem] bg-brand-800 px-6 py-8 text-white">
                <h1 class="font-display text-3xl font-semibold">{{ $wedding->title }}</h1>
                <p class="mt-2 text-sm text-white/80">{{ $wedding->event_date->translatedFormat('l, j F Y') }} · {{ $wedding->city }}, {{ $wedding->state }}</p>
            </div>
        </div>
    @endif

    {{-- resources/js/components/customer/CustomerDashboardPage.vue; the next
         steps as plain links are what shows before it mounts. --}}
    <div data-vue="customer-dashboard-page" data-props="@vueProps([...$props, 'part' => 'steps'])">
        @if ($wedding)
            <ol class="flex flex-col gap-2 rounded-[1.75rem] border border-gold-300/60 bg-surface-raised p-5">
                @foreach ($props['steps'] as $step)
                    <li>
                        <a href="{{ $step['url'] }}" class="flex flex-col rounded-2xl px-3 py-2 hover:bg-surface-muted">
                            <span class="font-semibold">{{ $step['title'] }}</span>
                            <span class="text-sm text-ink-muted">{{ $step['hint'] }} {{ $step['action'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ol>
        @endif
    </div>

    @if ($wedding)
        {{-- Planning together, right after the steps (at the foot of the page it
             went unseen). Server-rendered: it is the one piece of this page
             that shows another person's name, and the "Jemput pasangan" step
             links here. --}}
        <x-wedding-couple :wedding="$wedding" id="pasangan" class="my-8 scroll-mt-24" />

        {{-- The shortcuts and the booked vendors: the same component, second part. --}}
        <div data-vue="customer-dashboard-page" data-props="@vueProps([...$props, 'part' => 'rest'])"></div>
    @endif
</x-layouts.customer>
