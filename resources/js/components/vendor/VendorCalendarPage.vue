<script setup>
/**
 * A Pro vendor's calendar and online booking, in two tabs.
 *
 * Kalendar: a month you can read at a glance (weddings named on their day,
 * closed days struck through) and a panel for whichever day is tapped, where
 * that day is closed or reopened. Below, what is coming up.
 *
 * Tempahan Online: four numbered steps in the order a vendor goes through
 * them (how deposits are paid, the rules, an up-to-date calendar, then the
 * switch), each ticked when done, the first unfinished one open. Herepay is
 * the way in, with what it needs and how to get the keys; a bank transfer
 * stays as the fallback.
 *
 * Every form is an ordinary post; the tab and step it came from are
 * remembered for the one page load that comes back from it.
 */
import { computed, onMounted, ref } from 'vue';
import { t as tr } from '../../i18n.js';
import UiBadge from '../ui/UiBadge.vue';

const props = defineProps({
    status: { type: Object, required: true },
    steps: { type: Object, required: true },
    online: { type: Object, required: true },
    calendar: { type: Object, required: true },
    rules: { type: Object, required: true },
    deposit: { type: Object, required: true },
    ical: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const STORE_KEY = 'neekah:calendar-return';
const STEPS = ['deposit', 'rules', 'calendar', 'live'];
const ERROR_PLACES = {
    kalendar: ['from', 'to', 'reason', 'slots'],
    deposit: ['herepay_secret_key', 'herepay_private_key', 'herepay_api_key', 'manual_instructions'],
    rules: ['deposit_type', 'deposit_value', 'available_weekdays', 'max_per_day', 'min_lead_days', 'max_advance_months', 'deposit_terms'],
    calendar: ['ical_url'],
    live: ['enabled'],
};

const lang = document.documentElement.lang === 'en' ? 'en-MY' : 'ms-MY';
const input = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/30 focus:outline-none';

/* ---------------- Where the page opens ---------------- */

const tab = ref('kalendar');
const openStep = ref(STEPS.find((step) => !props.steps[step]) ?? null);
const doneCount = computed(() => STEPS.filter((step) => props.steps[step]).length);

const remember = (place) => {
    try {
        window.sessionStorage.setItem(STORE_KEY, place);
    } catch {
        // Nowhere to remember it: the page opens on the calendar.
    }
};

onMounted(() => {
    let returning = null;
    try {
        returning = window.sessionStorage.getItem(STORE_KEY);
        window.sessionStorage.removeItem(STORE_KEY);
    } catch {
        returning = null;
    }

    const withError = Object.keys(ERROR_PLACES).find((place) => ERROR_PLACES[place].some((field) => props.errors[field]));
    const place = withError ?? returning ?? new URLSearchParams(window.location.search).get('tab');

    if (place === 'kalendar' || !place) return;

    tab.value = 'tempahan';
    if (STEPS.includes(place)) openStep.value = place;
});

const toggleStep = (step) => (openStep.value = openStep.value === step ? null : step);

/* ---------------- Calendar ---------------- */

const iso = (date) => `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
const parse = (value) => new Date(`${value}T00:00:00`);
const today = parse(props.calendar.today);

const byDate = (list) => {
    const grouped = {};
    list.forEach((item) => (grouped[item.date] ??= []).push(item));
    return grouped;
};
const bookedOn = computed(() => byDate(props.calendar.booked));
const closedOn = computed(() => byDate(props.calendar.closed));

const monthOffset = ref(0);
const month = computed(() => {
    const first = new Date(today.getFullYear(), today.getMonth() + monthOffset.value, 1);
    const cells = Array.from({ length: (first.getDay() + 6) % 7 }, () => null);
    const last = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();

    for (let day = 1; day <= last; day++) {
        cells.push(iso(new Date(first.getFullYear(), first.getMonth(), day)));
    }

    return { label: first.toLocaleDateString(lang, { month: 'long', year: 'numeric' }), cells };
});

const weekdays = Array.from({ length: 7 }, (_, index) => new Date(2024, 0, 1 + index).toLocaleDateString(lang, { weekday: 'short' }));

const selected = ref(props.calendar.today);
const select = (date) => {
    selected.value = date;
    const target = parse(date);
    monthOffset.value = (target.getFullYear() - today.getFullYear()) * 12 + target.getMonth() - today.getMonth();
    closing.value = { reason: '', slots: '', range: false, to: '' };
};

const isPast = (date) => date < props.calendar.today;
const isFull = (date) => (bookedOn.value[date]?.length ?? 0) >= props.calendar.capacity;
const dayLabel = (date, style = 'long') => parse(date).toLocaleDateString(lang, style === 'long' ? { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' } : { weekday: 'short', day: 'numeric', month: 'short' });
const firstName = (name) => (name || '').split(' ')[0];

const closing = ref({ reason: '', slots: '', range: false, to: '' });
/** Several places a day: an outside booking can take just some of them. */
const sharedDays = computed(() => props.calendar.capacity > 1);
const canClose = computed(() => !isPast(selected.value) && !closedOn.value[selected.value]?.length && !isFull(selected.value));

/** Bookings and closed days in one list, by date, with the month they fall in. */
const agenda = computed(() =>
    [
        ...props.calendar.booked.map((booking) => ({ kind: 'booked', key: `b-${booking.reference}`, ...booking })),
        ...props.calendar.closed.map((date) => ({ kind: 'closed', key: `c-${date.id}`, ...date })),
    ]
        .sort((a, b) => a.date.localeCompare(b.date))
        .slice(0, 30),
);

/* ---------------- Booking rules ---------------- */

const rules = ref({ ...props.rules });
const toggleWeekday = (day) => {
    rules.value.weekdays = rules.value.weekdays.includes(day) ? rules.value.weekdays.filter((value) => value !== day) : [...rules.value.weekdays, day].sort();
};

const exampleDeposit = computed(() => {
    const price = props.rules.examplePrice;
    const value = Number(rules.value.deposit_value) || 0;
    const amount = rules.value.deposit_type === 'percent' ? (price * Math.min(100, Math.max(0, value))) / 100 : value;

    return Math.min(Math.max(amount, 1), Math.max(price, 1));
});
const money = (amount) => `RM${Number(amount).toLocaleString(lang, { maximumFractionDigits: 2 })}`;

/** The settings as saved, for the summary beside the steps. */
const summary = computed(() => {
    const saved = props.rules;
    const days = saved.weekdayOptions.filter((day) => saved.weekdays.includes(day.value)).map((day) => day.label);

    return [
        { label: tr('calendar.summary_payment'), value: props.deposit.channel ? tr(`calendar.summary_payment_${props.deposit.channel}`) : tr('calendar.summary_none'), muted: !props.deposit.channel },
        { label: tr('calendar.summary_deposit'), value: props.steps.rules ? (saved.deposit_type === 'percent' ? `${Number(saved.deposit_value)}%` : money(saved.deposit_value)) : '—', muted: !props.steps.rules },
        { label: tr('calendar.summary_days'), value: props.steps.rules ? (days.length === 7 ? tr('calendar.summary_every_day') : days.join(', ')) : '—', muted: !props.steps.rules },
        { label: tr('calendar.max_per_day'), value: props.steps.rules ? String(saved.max_per_day) : '—', muted: !props.steps.rules },
        { label: tr('calendar.summary_window'), value: props.steps.rules ? tr('calendar.summary_window_value', { lead: saved.min_lead_days, months: saved.max_advance_months }) : '—', muted: !props.steps.rules },
        { label: 'Google Calendar', value: props.ical.connected ? tr('calendar.summary_connected') : tr('calendar.summary_not_connected'), muted: !props.ical.connected },
    ];
});

const stepSummary = computed(() => ({
    deposit: props.deposit.channel ? `calendar.channel_${props.deposit.channel}` : 'calendar.step_deposit_todo',
    rules: props.steps.rules ? 'calendar.step_rules_done' : 'calendar.step_rules_todo',
    calendar: props.steps.calendar ? 'calendar.step_calendar_done' : 'calendar.step_calendar_todo',
    live: props.online.enabled ? 'calendar.step_live_done' : 'calendar.step_live_todo',
}));
</script>

<template>
    <div class="flex min-w-0 flex-col gap-6">
        <!-- The two halves of the page. -->
        <div class="grid grid-cols-2 gap-1 rounded-2xl border border-line bg-surface-raised p-1 sm:inline-grid sm:w-fit" role="tablist">
            <button
                v-for="name in ['kalendar', 'tempahan']"
                :key="name"
                type="button"
                role="tab"
                :aria-selected="tab === name"
                :class="['flex items-center justify-center gap-2 rounded-xl px-5 py-2.5 text-sm font-semibold transition', tab === name ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-muted hover:text-ink']"
                @click="tab = name"
            >
                {{ $t(`calendar.tab_${name}`) }}
                <span
                    v-if="name === 'tempahan'"
                    :class="['rounded-full px-2 py-0.5 text-[11px] font-bold', status.open ? (tab === name ? 'bg-white/20' : 'bg-emerald-100 text-emerald-800') : tab === name ? 'bg-white/20' : 'bg-amber-100 text-amber-800']"
                >{{ status.open ? $t('calendar.live_short') : `${doneCount}/4` }}</span>
            </button>
        </div>

        <!-- ======================= Kalendar ======================= -->
        <div v-show="tab === 'kalendar'" class="flex min-w-0 flex-col gap-6">
            <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-4 sm:p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-1">
                            <button type="button" class="flex size-9 items-center justify-center rounded-full text-lg text-ink-muted transition hover:bg-surface-muted" :aria-label="$t('calendar.previous_month')" @click="monthOffset--">‹</button>
                            <p class="min-w-40 text-center font-display text-lg font-semibold capitalize">{{ month.label }}</p>
                            <button type="button" class="flex size-9 items-center justify-center rounded-full text-lg text-ink-muted transition hover:bg-surface-muted" :aria-label="$t('calendar.next_month')" @click="monthOffset++">›</button>
                        </div>
                        <button v-if="monthOffset !== 0" type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="select(calendar.today)">{{ $t('calendar.today') }}</button>
                    </div>

                    <div class="grid grid-cols-7 gap-1 text-center text-[11px] font-semibold tracking-wide text-ink-muted uppercase">
                        <span v-for="label in weekdays" :key="label">{{ label }}</span>
                    </div>
                    <div class="-mt-2 grid grid-cols-7 gap-1">
                        <template v-for="(date, at) in month.cells" :key="at">
                            <span v-if="!date"></span>
                            <button
                                v-else
                                type="button"
                                :class="[
                                    'relative flex aspect-square min-w-0 flex-col items-start overflow-hidden rounded-xl border p-1.5 text-left transition sm:aspect-auto sm:min-h-20',
                                    bookedOn[date] ? 'border-emerald-200 bg-emerald-50' : closedOn[date] ? 'border-line bg-surface-muted' : 'border-transparent hover:border-brand-200 hover:bg-brand-50/50',
                                    isPast(date) ? 'opacity-40' : '',
                                    selected === date ? 'ring-2 ring-brand-600 ring-offset-1' : '',
                                ]"
                                @click="select(date)"
                            >
                                <span :class="['flex size-6 items-center justify-center rounded-full text-xs font-semibold', date === calendar.today ? 'bg-brand-600 text-white' : closedOn[date] ? 'text-ink-muted line-through' : '']">{{ parse(date).getDate() }}</span>
                                <span v-if="bookedOn[date]" class="mt-auto hidden w-full truncate rounded-md bg-emerald-600 px-1.5 py-0.5 text-[10px] font-semibold text-white sm:block">
                                    {{ firstName(bookedOn[date][0].customer) }}<template v-if="bookedOn[date].length > 1"> +{{ bookedOn[date].length - 1 }}</template>
                                </span>
                                <span v-else-if="closedOn[date]" class="mt-auto hidden w-full truncate text-[10px] font-medium text-ink-muted sm:block">{{ closedOn[date][0].imported ? 'Google' : $t('calendar.closed_short') }}</span>
                                <span v-if="bookedOn[date] || closedOn[date]" :class="['absolute right-1.5 bottom-1.5 size-1.5 rounded-full sm:hidden', bookedOn[date] ? 'bg-emerald-600' : 'bg-ink-muted']" aria-hidden="true"></span>
                            </button>
                        </template>
                    </div>

                    <ul class="flex flex-wrap gap-x-5 gap-y-2 border-t border-line pt-3 text-xs text-ink-muted">
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded border border-emerald-200 bg-emerald-50"></span>{{ $t('calendar.legend_booked') }}</li>
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded bg-surface-muted"></span>{{ $t('calendar.legend_closed') }}</li>
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded-full bg-brand-600"></span>{{ $t('calendar.today') }}</li>
                    </ul>
                </section>

                <!-- The day that was tapped. -->
                <aside class="flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 lg:sticky lg:top-24 lg:self-start">
                    <div>
                        <p class="text-xs font-semibold tracking-wide text-gold-600 uppercase">{{ $t('calendar.selected_day') }}</p>
                        <h2 class="mt-1 font-display text-xl font-semibold first-letter:uppercase">{{ dayLabel(selected) }}</h2>
                    </div>

                    <ul v-if="bookedOn[selected]" class="flex flex-col gap-2">
                        <li v-for="booking in bookedOn[selected]" :key="booking.reference">
                            <a :href="booking.url" class="flex min-w-0 items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 transition hover:border-emerald-400">
                                <span class="text-lg" aria-hidden="true">💍</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold">{{ booking.customer }}</span>
                                    <span class="block truncate text-xs text-ink-muted">{{ booking.reference }}</span>
                                </span>
                                <UiBadge :label="booking.status_label" :tone="booking.status_tone" />
                            </a>
                        </li>
                    </ul>

                    <div v-for="entry in closedOn[selected] || []" :key="entry.id" class="flex flex-col gap-2 rounded-xl bg-surface-muted p-3 text-sm">
                        <p class="font-semibold">⛔ {{ entry.slots ? $t('calendar.slots_taken', { count: entry.slots, capacity: calendar.capacity }) : $t('calendar.closed_all_day') }}</p>
                        <p v-if="entry.reason" class="text-ink-muted">{{ entry.reason }}</p>
                        <p v-if="entry.imported" class="text-xs text-sky-800">{{ $t('calendar.from_google') }}</p>
                        <form v-else-if="entry.destroy_url" :action="entry.destroy_url" method="POST" @submit="remember('kalendar')">
                            <input type="hidden" name="_token" :value="csrf">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="rounded-full border border-line bg-surface-raised px-4 py-1.5 text-xs font-semibold transition hover:border-brand-400">{{ $t('calendar.reopen') }}</button>
                        </form>
                    </div>

                    <p v-if="isPast(selected)" class="text-sm text-ink-muted">{{ $t('calendar.past_day') }}</p>
                    <p v-else-if="isFull(selected)" class="text-sm text-ink-muted">{{ $t('calendar.full_day') }}</p>
                    <p v-else-if="!bookedOn[selected] && !closedOn[selected]" class="flex items-center gap-2 text-sm text-emerald-700"><span class="size-2 rounded-full bg-emerald-500"></span>{{ $t('calendar.open_day') }}</p>

                    <!-- Close it, or a run of days from it. -->
                    <form v-if="canClose" :action="calendar.storeUrl" method="POST" class="flex flex-col gap-3 border-t border-line pt-4" @submit="remember('kalendar')">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="from" :value="selected">
                        <p class="text-sm font-semibold">{{ $t('calendar.close_title') }}</p>
                        <input v-model="closing.reason" name="reason" maxlength="255" :placeholder="$t('calendar.reason_placeholder')" :class="input">
                        <select v-if="sharedDays" v-model="closing.slots" name="slots" :class="['nk-select pr-10', input]">
                            <option value="">{{ $t('calendar.whole_day') }}</option>
                            <option v-for="count in calendar.capacity - 1" :key="count" :value="count">{{ $t('calendar.slots_taken', { count, capacity: calendar.capacity }) }}</option>
                        </select>
                        <label class="flex items-center gap-2 text-sm">
                            <input v-model="closing.range" type="checkbox" class="accent-brand-600">
                            {{ $t('calendar.close_range') }}
                        </label>
                        <label v-if="closing.range" class="flex flex-col gap-1.5">
                            <span class="text-xs font-medium">{{ $t('calendar.until') }}</span>
                            <input v-model="closing.to" type="date" name="to" :min="selected" required :class="input">
                        </label>
                        <span v-if="errors.from || errors.to" class="text-xs text-brand-700">{{ errors.from || errors.to }}</span>
                        <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ closing.range ? $t('calendar.close_days') : $t('calendar.close_day') }}</button>
                    </form>

                    <!-- Keeping it true, which keeps online booking open. -->
                    <form :action="status.confirmUrl" method="POST" class="flex flex-col gap-2 rounded-xl bg-ivory p-3" @submit="remember('kalendar')">
                        <input type="hidden" name="_token" :value="csrf">
                        <p class="text-xs text-ink-muted">{{ status.confirmed ? $t('calendar.confirmed_short', { ago: status.confirmed }) : $t('calendar.never_confirmed_short') }}</p>
                        <button type="submit" class="self-start text-xs font-semibold text-brand-700 hover:underline">✓ {{ $t('calendar.confirm') }}</button>
                    </form>
                </aside>
            </div>

            <!-- Coming up. -->
            <section class="flex min-w-0 flex-col gap-3">
                <h2 class="font-display text-xl font-semibold">{{ $t('calendar.upcoming') }}</h2>
                <p v-if="!agenda.length" class="rounded-2xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">{{ $t('calendar.upcoming_empty') }}</p>
                <ul v-else class="flex flex-col divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface-raised">
                    <li v-for="entry in agenda" :key="entry.key">
                        <button type="button" class="flex w-full min-w-0 items-center gap-4 px-4 py-3 text-left text-sm transition hover:bg-surface-muted/60" @click="select(entry.date)">
                            <span class="w-12 shrink-0 rounded-xl bg-surface-muted py-1 text-center">
                                <span class="block font-display text-lg leading-none font-semibold">{{ parse(entry.date).getDate() }}</span>
                                <span class="block text-[10px] font-semibold text-ink-muted uppercase">{{ parse(entry.date).toLocaleDateString(lang, { month: 'short' }) }}</span>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block truncate font-medium">{{ entry.kind === 'booked' ? entry.customer : entry.reason || $t('calendar.closed_all_day') }}</span>
                                <span class="block truncate text-xs text-ink-muted">{{ entry.kind === 'booked' ? entry.reference : entry.imported ? $t('calendar.from_google') : $t('calendar.closed_by_you') }}</span>
                            </span>
                            <UiBadge v-if="entry.kind === 'booked'" :label="entry.status_label" :tone="entry.status_tone" />
                            <span v-else class="shrink-0 rounded-full bg-surface-muted px-2.5 py-1 text-xs text-ink-muted">{{ $t('calendar.closed_short') }}</span>
                        </button>
                    </li>
                </ul>
            </section>
        </div>

        <!-- ======================= Tempahan Online ======================= -->
        <!-- On a wide screen the steps take the left and the status and a
             summary sit beside them; on a phone they stack in reading order. -->
        <div v-show="tab === 'tempahan'" class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_22rem] lg:grid-rows-[auto_1fr] lg:gap-6">
            <!-- Where it stands. -->
            <section :class="['flex min-w-0 flex-col gap-4 rounded-2xl border p-5 sm:p-6 lg:col-start-2 lg:row-start-1 lg:self-start', status.open ? 'border-emerald-200 bg-emerald-50/60' : 'border-line bg-surface-raised']">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="font-display text-xl font-semibold">{{ status.open ? $t('calendar.live_title') : $t('calendar.setup_title') }}</p>
                        <p class="mt-1 text-sm text-ink-muted">{{ status.open ? $t('calendar.live_body') : status.label }}</p>
                    </div>
                    <a v-if="status.open" :href="status.publicUrl" target="_blank" rel="noopener" class="shrink-0 rounded-full border border-line bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('calendar.view_public') }} ↗</a>
                </div>
                <div>
                    <div class="flex justify-between text-xs font-medium text-ink-muted"><span>{{ $t('calendar.progress', { done: doneCount }) }}</span></div>
                    <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-muted">
                        <div class="h-full rounded-full bg-brand-600 transition-all" :style="{ width: `${(doneCount / 4) * 100}%` }"></div>
                    </div>
                </div>
            </section>

            <!-- The four steps. -->
            <ol class="flex min-w-0 flex-col gap-3 lg:col-start-1 lg:row-span-2 lg:row-start-1">
                <li v-for="(step, index) in STEPS" :key="step" class="overflow-hidden rounded-2xl border border-line bg-surface-raised">
                    <button type="button" class="flex w-full min-w-0 items-center gap-4 p-4 text-left sm:p-5" :aria-expanded="openStep === step" @click="toggleStep(step)">
                        <span :class="['flex size-9 shrink-0 items-center justify-center rounded-full text-sm font-bold', steps[step] ? 'bg-emerald-500 text-white' : 'bg-brand-50 text-brand-700 ring-1 ring-brand-200']">
                            <template v-if="steps[step]">✓</template><template v-else>{{ index + 1 }}</template>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block font-semibold">{{ $t(`calendar.step_${step}`) }}</span>
                            <span class="block truncate text-xs text-ink-muted">{{ $t(stepSummary[step]) }}</span>
                        </span>
                        <svg :class="['size-4 shrink-0 text-ink-muted transition', openStep === step ? 'rotate-180' : '']" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                    </button>

                    <!-- 1. How couples pay the deposit -->
                    <div v-if="step === 'deposit' && openStep === step" class="flex flex-col gap-5 border-t border-line p-4 sm:p-6">
                        <div v-if="deposit.connected" class="flex flex-col gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="font-semibold text-emerald-900">✓ {{ $t('calendar.herepay_connected', { environment: deposit.environment }) }}</p>
                                <p class="mt-0.5 text-emerald-900/80">{{ deposit.verified ? $t('calendar.herepay_verified', { date: deposit.verified }) : $t('calendar.herepay_unverified') }}</p>
                            </div>
                            <form :action="deposit.disconnectUrl" method="POST" class="shrink-0" @submit="remember('deposit')">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="rounded-full border border-emerald-300 bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('calendar.disconnect') }}</button>
                            </form>
                        </div>

                        <template v-else>
                            <div class="flex flex-col gap-2">
                                <span class="w-fit rounded-full bg-gold-300/40 px-2.5 py-0.5 text-[11px] font-bold tracking-wide text-brand-900 uppercase">{{ $t('calendar.recommended') }}</span>
                                <h3 class="font-display text-lg font-semibold">{{ $t('calendar.herepay_title') }}</h3>
                                <p class="text-sm text-ink-muted">{{ $t('calendar.herepay_intro') }}</p>
                            </div>

                            <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                                <div class="rounded-xl bg-ivory p-4">
                                    <p class="text-sm font-semibold">{{ $t('calendar.herepay_needs_title') }}</p>
                                    <ul class="mt-2 flex flex-col gap-2 text-sm text-ink-muted">
                                        <li v-for="need in ['needs_business', 'needs_ssm', 'needs_bank']" :key="need" class="flex gap-2"><span class="text-emerald-600" aria-hidden="true">✓</span><span>{{ $t(`calendar.${need}`) }}</span></li>
                                    </ul>
                                </div>
                                <div class="rounded-xl bg-ivory p-4">
                                    <p class="text-sm font-semibold">{{ $t('calendar.herepay_how_title') }}</p>
                                    <ol class="mt-2 flex list-decimal flex-col gap-2 pl-4 text-sm text-ink-muted">
                                        <li>{{ $t('calendar.how_register') }} <a :href="deposit.registerUrl" target="_blank" rel="noopener" class="font-semibold text-brand-700 underline underline-offset-2">{{ $t('calendar.register_link') }} ↗</a></li>
                                        <li>{{ $t('calendar.how_verify') }}</li>
                                        <li>{{ $t('calendar.how_keys') }} <a :href="deposit.keysGuideUrl" target="_blank" rel="noopener" class="font-semibold text-brand-700 underline underline-offset-2">{{ $t('calendar.keys_guide') }} ↗</a></li>
                                        <li>{{ $t('calendar.how_paste') }}</li>
                                    </ol>
                                </div>
                            </div>

                            <form :action="deposit.connectUrl" method="POST" class="flex flex-col gap-4 rounded-xl border border-line p-4" @submit="remember('deposit')">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="PUT">
                                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                                    <label class="flex min-w-0 flex-col gap-1.5">
                                        <span class="text-xs font-medium">{{ $t('calendar.herepay_secret') }}</span>
                                        <input type="password" name="herepay_secret_key" autocomplete="off" required :class="input">
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1.5">
                                        <span class="text-xs font-medium">{{ $t('calendar.herepay_private') }}</span>
                                        <input type="password" name="herepay_private_key" autocomplete="off" required :class="input">
                                    </label>
                                    <label class="flex min-w-0 flex-col gap-1.5 sm:col-span-2">
                                        <span class="text-xs font-medium">{{ $t('calendar.herepay_api_key') }}</span>
                                        <input type="password" name="herepay_api_key" autocomplete="off" :class="input">
                                        <span class="text-xs text-ink-muted">{{ $t('calendar.herepay_api_key_help') }}</span>
                                    </label>
                                </div>
                                <span v-if="errors.herepay_secret_key || errors.herepay_private_key" class="-mt-2 text-xs text-brand-700">{{ errors.herepay_secret_key || errors.herepay_private_key }}</span>
                                <p class="text-xs text-ink-muted">{{ $t('calendar.herepay_test_note', { environment: deposit.environment }) }}</p>
                                <button type="submit" class="self-start rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('calendar.herepay_connect') }}</button>
                            </form>
                        </template>

                        <!-- The fallback. -->
                        <details v-if="deposit.manualOffered" :open="deposit.channel === 'manual' || !!errors.manual_instructions" class="rounded-xl border border-dashed border-line p-4">
                            <summary class="cursor-pointer text-sm font-semibold">{{ deposit.connected ? $t('calendar.manual_title_backup') : $t('calendar.manual_title') }}</summary>
                            <form :action="deposit.manualUrl" method="POST" class="mt-3 flex flex-col gap-3" @submit="remember('deposit')">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="PUT">
                                <p class="text-sm text-ink-muted">{{ $t('calendar.manual_intro') }}</p>
                                <textarea name="manual_instructions" rows="3" maxlength="1000" :value="deposit.manualInstructions" :placeholder="$t('calendar.manual_placeholder')" :class="input"></textarea>
                                <span v-if="errors.manual_instructions" class="text-xs text-brand-700">{{ errors.manual_instructions }}</span>
                                <button type="submit" class="self-start rounded-full border border-brand-600 px-5 py-2 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ $t('calendar.manual_save') }}</button>
                            </form>
                        </details>
                    </div>

                    <!-- 2. The rules couples book under -->
                    <form v-if="step === 'rules' && openStep === step" :action="rules.url" method="POST" class="flex flex-col gap-6 border-t border-line p-4 sm:p-6" @submit="remember('rules')">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="_method" value="PUT">

                        <fieldset class="flex flex-col gap-3">
                            <legend class="text-sm font-semibold">{{ $t('calendar.deposit_title') }}</legend>
                            <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                                <div class="grid grid-cols-2 gap-1 self-start rounded-xl bg-surface-muted p-1">
                                    <label v-for="type in rules.depositTypes" :key="type.value" class="cursor-pointer">
                                        <input v-model="rules.deposit_type" type="radio" name="deposit_type" :value="type.value" class="peer sr-only">
                                        <span class="block rounded-lg px-3 py-2 text-center text-sm font-medium text-ink-muted transition peer-checked:bg-surface-raised peer-checked:text-ink peer-checked:shadow-sm">{{ type.label }}</span>
                                    </label>
                                </div>
                                <label class="flex min-w-0 flex-col gap-1.5">
                                    <span class="sr-only">{{ rules.deposit_type === 'percent' ? $t('calendar.deposit_percent') : $t('calendar.deposit_amount') }}</span>
                                    <div class="relative">
                                        <span class="pointer-events-none absolute top-1/2 left-4 -translate-y-1/2 text-sm text-ink-muted">{{ rules.deposit_type === 'percent' ? '%' : 'RM' }}</span>
                                        <input v-model="rules.deposit_value" type="number" name="deposit_value" step="0.01" min="1" :max="rules.deposit_type === 'percent' ? 100 : undefined" required :class="[input, 'pl-12']">
                                    </div>
                                </label>
                            </div>
                            <p class="text-xs text-ink-muted">{{ $t('calendar.deposit_example', { price: money(rules.examplePrice), deposit: money(exampleDeposit) }) }}<template v-if="rules.perPax"> · {{ $t('calendar.pax_fixed') }}</template></p>
                            <span v-if="errors.deposit_type || errors.deposit_value" class="text-xs text-brand-700">{{ errors.deposit_type || errors.deposit_value }}</span>
                        </fieldset>

                        <fieldset class="flex flex-col gap-3">
                            <legend class="text-sm font-semibold">{{ $t('calendar.weekdays_title') }}</legend>
                            <div class="grid grid-cols-7 gap-1.5">
                                <label v-for="day in rules.weekdayOptions" :key="day.value" class="cursor-pointer" :title="day.full">
                                    <input type="checkbox" name="available_weekdays[]" :value="day.value" :checked="rules.weekdays.includes(day.value)" class="peer sr-only" @change="toggleWeekday(day.value)">
                                    <span class="block rounded-xl border border-line py-2.5 text-center text-sm font-medium text-ink-muted transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400">{{ day.label }}</span>
                                </label>
                            </div>
                            <span v-if="errors.available_weekdays" class="text-xs text-brand-700">{{ errors.available_weekdays }}</span>
                        </fieldset>

                        <fieldset class="grid min-w-0 gap-4 sm:grid-cols-3">
                            <legend class="mb-3 text-sm font-semibold sm:col-span-3">{{ $t('calendar.limits_title') }}</legend>
                            <label v-for="field in [['max_per_day', 1, rules.maxPerDay], ['min_lead_days', 1, 365], ['max_advance_months', 1, 36]]" :key="field[0]" class="flex min-w-0 flex-col gap-1.5">
                                <span class="text-xs font-medium">{{ $t(`calendar.${field[0]}`) }}</span>
                                <input v-model="rules[field[0]]" type="number" :name="field[0]" :min="field[1]" :max="field[2]" required :class="input">
                                <span class="text-xs text-ink-muted">{{ $t(`calendar.${field[0]}_help`) }}</span>
                            </label>
                        </fieldset>

                        <label class="flex min-w-0 flex-col gap-1.5">
                            <span class="text-sm font-semibold">{{ $t('calendar.deposit_terms') }}</span>
                            <textarea v-model="rules.deposit_terms" name="deposit_terms" rows="4" maxlength="3000" :placeholder="$t('calendar.deposit_terms_placeholder')" :class="input"></textarea>
                            <span class="text-xs text-ink-muted">{{ $t('calendar.deposit_terms_help') }}</span>
                        </label>

                        <button type="submit" class="self-start rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('calendar.save_rules') }}</button>
                    </form>

                    <!-- 3. An up-to-date calendar -->
                    <div v-if="step === 'calendar' && openStep === step" class="flex flex-col gap-5 border-t border-line p-4 sm:p-6">
                        <p class="text-sm text-ink-muted">{{ $t('calendar.calendar_intro', { days: status.freshDays }) }}</p>
                        <form :action="status.confirmUrl" method="POST" class="flex flex-col gap-3 rounded-xl bg-ivory p-4 sm:flex-row sm:items-center sm:justify-between" @submit="remember('calendar')">
                            <input type="hidden" name="_token" :value="csrf">
                            <p class="text-sm">{{ status.confirmed ? $t('calendar.confirmed_short', { ago: status.confirmed }) : $t('calendar.never_confirmed_short') }}</p>
                            <button type="submit" class="shrink-0 rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('calendar.confirm') }}</button>
                        </form>

                        <div class="flex flex-col gap-3">
                            <div>
                                <h3 class="font-semibold">{{ $t('calendar.ical_title') }} <span class="text-xs font-normal text-ink-muted">· {{ $t('calendar.optional') }}</span></h3>
                                <p class="mt-1 text-sm text-ink-muted">{{ $t('calendar.ical_intro') }}</p>
                            </div>
                            <div v-if="ical.connected" class="flex flex-col gap-3 rounded-xl bg-sky-50 p-4 text-sm">
                                <p class="font-semibold break-all text-sky-900">{{ ical.masked }}</p>
                                <p v-if="ical.error || ical.synced" class="text-sky-900/80">{{ ical.error ? $t('calendar.ical_last_error', { error: ical.error }) : $t('calendar.ical_synced_ago', { ago: ical.synced }) }}</p>
                                <div class="flex flex-wrap gap-2">
                                    <form :action="ical.syncUrl" method="POST" @submit="remember('calendar')">
                                        <input type="hidden" name="_token" :value="csrf">
                                        <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('calendar.ical_sync_now') }}</button>
                                    </form>
                                    <form :action="ical.disconnectUrl" method="POST" @submit="remember('calendar')">
                                        <input type="hidden" name="_token" :value="csrf">
                                        <input type="hidden" name="_method" value="DELETE">
                                        <button type="submit" class="rounded-full border border-sky-300 bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('calendar.disconnect') }}</button>
                                    </form>
                                </div>
                            </div>
                            <form :action="ical.connectUrl" method="POST" class="flex flex-col gap-2 sm:flex-row sm:items-start" @submit="remember('calendar')">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="PUT">
                                <label class="flex min-w-0 flex-1 flex-col gap-1.5">
                                    <span class="sr-only">{{ $t('calendar.ical_url') }}</span>
                                    <input type="url" name="ical_url" required :placeholder="ical.connected ? $t('calendar.ical_replace') : 'https://calendar.google.com/calendar/ical/…/basic.ics'" :class="input">
                                    <span v-if="errors.ical_url" class="text-xs text-brand-700">{{ errors.ical_url }}</span>
                                    <span v-else class="text-xs text-ink-muted">{{ $t('calendar.ical_help') }}</span>
                                </label>
                                <button type="submit" class="shrink-0 rounded-full border border-brand-600 px-5 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ $t('calendar.ical_connect') }}</button>
                            </form>
                        </div>
                    </div>

                    <!-- 4. The switch -->
                    <form v-if="step === 'live' && openStep === step" :action="online.toggleUrl" method="POST" class="flex flex-col gap-4 border-t border-line p-4 sm:p-6" @submit="remember('live')">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="_method" value="PUT">
                        <input type="hidden" name="enabled" :value="online.enabled ? 0 : 1">
                        <p class="text-sm text-ink-muted">{{ $t('calendar.live_intro') }}</p>
                        <p v-if="!steps.deposit" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $t('calendar.live_needs_deposit') }}</p>
                        <p v-if="!online.hasPackages" class="rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-900">{{ $t('calendar.live_needs_packages') }} <a :href="online.packagesUrl" class="font-semibold underline underline-offset-2">{{ $t('calendar.add_package') }}</a></p>
                        <span v-if="errors.enabled" class="text-sm text-brand-700">{{ errors.enabled }}</span>
                        <button
                            type="submit"
                            :disabled="!online.enabled && !steps.deposit"
                            :class="['self-start rounded-full px-6 py-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-50', online.enabled ? 'border border-line bg-surface-raised hover:border-brand-400' : 'bg-emerald-600 text-white hover:bg-emerald-700']"
                        >{{ online.enabled ? $t('calendar.turn_off') : $t('calendar.turn_on') }}</button>
                    </form>
                </li>
            </ol>

            <aside class="flex min-w-0 flex-col gap-4 lg:col-start-2 lg:row-start-2 lg:self-start">
                <!-- What is set now, at a glance. -->
                <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                    <h2 class="font-semibold">{{ $t('calendar.summary_title') }}</h2>
                    <dl class="flex flex-col divide-y divide-line text-sm">
                        <div v-for="row in summary" :key="row.label" class="flex items-baseline justify-between gap-4 py-2">
                            <dt class="text-ink-muted">{{ row.label }}</dt>
                            <dd :class="['text-right font-medium', row.muted ? 'text-ink-muted' : '']">{{ row.value }}</dd>
                        </div>
                    </dl>
                </section>

                <!-- What the couple goes through. -->
                <section class="flex flex-col gap-3 rounded-2xl border border-gold-300/60 bg-gold-300/10 p-5">
                    <h2 class="font-semibold">{{ $t('calendar.couple_title') }}</h2>
                    <ol class="flex flex-col gap-3 text-sm">
                        <li v-for="(line, index) in ['couple_pick', 'couple_pay', 'couple_confirmed']" :key="line" class="flex gap-3">
                            <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-surface-raised text-xs font-bold text-brand-700 ring-1 ring-gold-300">{{ index + 1 }}</span>
                            <span class="text-ink-muted">{{ $t(`calendar.${line}`) }}</span>
                        </li>
                    </ol>
                </section>
            </aside>
        </div>
    </div>
</template>
