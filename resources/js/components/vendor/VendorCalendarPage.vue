<script setup>
/**
 * A Pro vendor's calendar and online booking, one page instead of two.
 *
 * A status bar says whether couples can book online right now (and why not),
 * then four tabs keep it uncluttered: the calendar itself, the booking
 * rules, where the deposit goes, and Google Calendar. Every form is an
 * ordinary post; the tab a form was sent from is remembered for the one
 * page load that comes back from it.
 */
import { computed, onMounted, ref } from 'vue';
import UiBadge from '../ui/UiBadge.vue';

const props = defineProps({
    status: { type: Object, required: true },
    calendar: { type: Object, required: true },
    rules: { type: Object, required: true },
    deposit: { type: Object, required: true },
    ical: { type: Object, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const TAB_KEY = 'neekah:calendar-tab';
const TABS = ['kalendar', 'tempahan', 'deposit', 'google'];
const ERROR_TABS = {
    kalendar: ['from', 'to', 'reason', 'slots'],
    tempahan: ['deposit_type', 'deposit_value', 'available_weekdays', 'max_per_day', 'min_lead_days', 'max_advance_months', 'deposit_terms', 'manual_instructions'],
    deposit: ['herepay_secret_key', 'herepay_private_key'],
    google: ['ical_url'],
};

const tab = ref('kalendar');
const remember = (name) => {
    try {
        window.sessionStorage.setItem(TAB_KEY, name);
    } catch {
        // Nowhere to remember it: the page opens on the calendar.
    }
};

onMounted(() => {
    const fromUrl = new URLSearchParams(window.location.search).get('tab');
    const withError = Object.keys(ERROR_TABS).find((name) => ERROR_TABS[name].some((field) => props.errors[field]));
    let stored = null;

    // Only for the page a form came back to, then forgotten.
    try {
        stored = window.sessionStorage.getItem(TAB_KEY);
        window.sessionStorage.removeItem(TAB_KEY);
    } catch {
        stored = null;
    }

    tab.value = [withError, fromUrl, stored].find((name) => TABS.includes(name)) ?? 'kalendar';
});

const lang = document.documentElement.lang === 'en' ? 'en-MY' : 'ms-MY';
const input = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:ring-2 focus:ring-brand-400/30 focus:outline-none';
const card = 'flex min-w-0 flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6';

/* ---------- Calendar ---------- */

const closedByDate = computed(() => Object.fromEntries(props.calendar.closed.map((date) => [date.date, date])));
const bookedByDate = computed(() => {
    const byDate = {};
    props.calendar.booked.forEach((booking) => (byDate[booking.date] ??= []).push(booking));

    return byDate;
});

const monthOffset = ref(0);
const month = computed(() => {
    const start = new Date(`${props.calendar.today}T00:00:00`);
    const first = new Date(start.getFullYear(), start.getMonth() + monthOffset.value, 1);
    const days = Array.from({ length: (first.getDay() + 6) % 7 }, () => null);
    const last = new Date(first.getFullYear(), first.getMonth() + 1, 0).getDate();

    for (let day = 1; day <= last; day++) {
        days.push({ day, iso: `${first.getFullYear()}-${String(first.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}` });
    }

    return { label: first.toLocaleDateString(lang, { month: 'long', year: 'numeric' }), days };
});

const weekdayLabels = computed(() =>
    Array.from({ length: 7 }, (_, index) => new Date(2024, 0, 1 + index).toLocaleDateString(lang, { weekday: 'narrow' })),
);

const stateOf = (iso) => {
    if (bookedByDate.value[iso]) return 'booked';
    if (closedByDate.value[iso]) return 'closed';
    return iso < props.calendar.today ? 'past' : 'open';
};

const form = ref({ from: '', to: '', reason: '', slots: '' });

/** Tap a free day to pick it; tap a later one to make it a range. */
const pick = (iso) => {
    if (stateOf(iso) !== 'open') return;

    if (form.value.from && !form.value.to && iso > form.value.from) {
        form.value.to = iso;
        return;
    }

    form.value = { ...form.value, from: iso, to: '' };
};

const clearPick = () => (form.value = { from: '', to: '', reason: '', slots: '' });
const inPick = (iso) => iso === form.value.from || (form.value.to && iso > form.value.from && iso <= form.value.to);
const format = (iso) => new Date(`${iso}T00:00:00`).toLocaleDateString(lang, { weekday: 'short', day: 'numeric', month: 'short' });
const pickedLabel = computed(() => (form.value.to ? `${format(form.value.from)} – ${format(form.value.to)}` : format(form.value.from)));

/** Bookings and closed days in one list, by date. */
const upcoming = computed(() =>
    [
        ...props.calendar.booked.map((booking) => ({ kind: 'booked', key: `b-${booking.reference}`, ...booking })),
        ...props.calendar.closed.map((date) => ({ kind: 'closed', key: `c-${date.id}`, ...date })),
    ].sort((a, b) => a.date.localeCompare(b.date)),
);

/* ---------- Booking rules ---------- */

const rules = ref({ ...props.rules });
const toggleDay = (day) => {
    rules.value.weekdays = rules.value.weekdays.includes(day) ? rules.value.weekdays.filter((value) => value !== day) : [...rules.value.weekdays, day].sort();
};

const exampleDeposit = computed(() => {
    const price = props.rules.examplePrice;
    const value = Number(rules.value.deposit_value) || 0;
    const amount = rules.value.deposit_type === 'percent' ? (price * Math.min(100, Math.max(0, value))) / 100 : value;

    return Math.min(Math.max(amount, 1), Math.max(price, 1));
});
const money = (amount) => `RM${Number(amount).toLocaleString(lang, { maximumFractionDigits: 2 })}`;
</script>

<template>
    <div class="flex min-w-0 flex-col gap-6">
        <!-- Can couples book online right now? -->
        <section
            :class="[
                'flex min-w-0 flex-col gap-4 rounded-2xl border p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5',
                status.open ? 'border-emerald-200 bg-emerald-50/70' : 'border-amber-200 bg-amber-50/70',
            ]"
        >
            <div class="flex min-w-0 items-start gap-3">
                <span :class="['mt-1.5 size-2.5 shrink-0 rounded-full', status.open ? 'bg-emerald-500' : 'bg-amber-500']" aria-hidden="true"></span>
                <div class="min-w-0">
                    <p class="text-sm font-semibold">{{ status.label }}</p>
                    <p class="mt-0.5 text-xs text-ink-muted">
                        {{ status.confirmed ? $t('calendar.confirmed_ago', { ago: status.confirmed, days: status.freshDays }) : $t('calendar.never_confirmed', { days: status.freshDays }) }}
                    </p>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap gap-2">
                <form :action="status.confirmUrl" method="POST" @submit="remember(tab)">
                    <input type="hidden" name="_token" :value="csrf">
                    <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('calendar.confirm') }}</button>
                </form>
                <a v-if="status.open" :href="status.publicUrl" target="_blank" rel="noopener" class="rounded-full border border-line bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('calendar.view_public') }}</a>
            </div>
        </section>

        <!-- Tabs -->
        <div class="no-scrollbar -mx-4 overflow-x-auto px-4 sm:mx-0 sm:px-0" role="tablist">
            <div class="inline-flex gap-1 rounded-full border border-line bg-surface-raised p-1">
                <button
                    v-for="name in ['kalendar', 'tempahan', 'deposit', 'google']"
                    :key="name"
                    type="button"
                    role="tab"
                    :aria-selected="tab === name"
                    :class="['rounded-full px-4 py-2 text-sm font-medium whitespace-nowrap transition', tab === name ? 'bg-brand-600 text-white shadow-sm' : 'text-ink-muted hover:text-ink']"
                    @click="tab = name"
                >{{ $t(`calendar.tab_${name}`) }}</button>
            </div>
        </div>

        <!-- ============ Calendar ============ -->
        <div v-show="tab === 'kalendar'" class="flex min-w-0 flex-col gap-6">
            <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section :class="card">
                    <div class="flex items-center justify-between gap-3">
                        <button type="button" class="flex size-9 items-center justify-center rounded-full text-lg text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="monthOffset <= 0" :aria-label="$t('calendar.previous_month')" @click="monthOffset--">‹</button>
                        <p class="font-display text-lg font-semibold capitalize">{{ month.label }}</p>
                        <button type="button" class="flex size-9 items-center justify-center rounded-full text-lg text-ink-muted transition hover:bg-surface-muted" :aria-label="$t('calendar.next_month')" @click="monthOffset++">›</button>
                    </div>

                    <div>
                        <div class="grid grid-cols-7 gap-1.5 text-center text-[11px] font-medium text-ink-muted uppercase">
                            <span v-for="(label, index) in weekdayLabels" :key="index">{{ label }}</span>
                        </div>
                        <div class="mt-2 grid grid-cols-7 gap-1.5">
                            <template v-for="(cell, at) in month.days" :key="at">
                                <span v-if="!cell"></span>
                                <button
                                    v-else
                                    type="button"
                                    :disabled="stateOf(cell.iso) !== 'open'"
                                    :title="bookedByDate[cell.iso]?.map((booking) => booking.reference).join(', ') || closedByDate[cell.iso]?.reason || ''"
                                    :class="[
                                        'relative flex aspect-square flex-col items-center justify-center rounded-xl text-sm transition',
                                        stateOf(cell.iso) === 'booked' ? 'bg-emerald-100 font-semibold text-emerald-800' : '',
                                        stateOf(cell.iso) === 'closed' ? 'bg-surface-muted text-ink-muted line-through' : '',
                                        stateOf(cell.iso) === 'past' ? 'text-ink-muted/35' : '',
                                        stateOf(cell.iso) === 'open' ? 'hover:bg-brand-50 hover:text-brand-700' : '',
                                        cell.iso === calendar.today ? 'font-semibold' : '',
                                        inPick(cell.iso) ? 'bg-brand-600! text-white! no-underline' : '',
                                    ]"
                                    @click="pick(cell.iso)"
                                >
                                    {{ cell.day }}
                                    <span v-if="cell.iso === calendar.today" class="absolute bottom-1.5 size-1 rounded-full bg-brand-500" aria-hidden="true"></span>
                                </button>
                            </template>
                        </div>
                    </div>

                    <ul class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-ink-muted">
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded bg-emerald-100"></span>{{ $t('calendar.legend_booked') }}</li>
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded bg-surface-muted"></span>{{ $t('calendar.legend_closed') }}</li>
                        <li class="flex items-center gap-1.5"><span class="size-3 rounded bg-brand-600"></span>{{ $t('calendar.legend_picked') }}</li>
                    </ul>
                </section>

                <!-- Closing the days picked on the grid. -->
                <form :action="calendar.storeUrl" method="POST" :class="[card, 'lg:self-start']" @submit="remember('kalendar')">
                    <input type="hidden" name="_token" :value="csrf">
                    <div>
                        <h2 class="font-semibold">{{ $t('calendar.close_title') }}</h2>
                        <p class="mt-1 text-xs text-ink-muted">{{ form.from ? pickedLabel : $t('calendar.close_hint') }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <label class="flex min-w-0 flex-col gap-1.5">
                            <span class="text-xs font-medium">{{ $t('calendar.from') }}</span>
                            <input v-model="form.from" type="date" name="from" :min="calendar.today" required :class="input">
                        </label>
                        <label class="flex min-w-0 flex-col gap-1.5">
                            <span class="text-xs font-medium">{{ $t('calendar.to') }}</span>
                            <input v-model="form.to" type="date" name="to" :min="form.from || calendar.today" :class="input">
                        </label>
                    </div>
                    <span v-if="errors.from || errors.to" class="-mt-3 text-xs text-brand-700">{{ errors.from || errors.to }}</span>

                    <label class="flex flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.reason') }}</span>
                        <input v-model="form.reason" name="reason" maxlength="255" :placeholder="$t('calendar.reason_placeholder')" :class="input">
                    </label>

                    <label v-if="calendar.capacity > 1" class="flex flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.how_much') }}</span>
                        <select v-model="form.slots" name="slots" :class="['nk-select pr-10', input]">
                            <option value="">{{ $t('calendar.whole_day') }}</option>
                            <option v-for="count in calendar.capacity - 1" :key="count" :value="count">{{ $t('calendar.slots_taken', { count, capacity: calendar.capacity }) }}</option>
                        </select>
                    </label>

                    <div class="flex gap-2">
                        <button type="submit" :disabled="!form.from" class="flex-1 rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50">{{ $t('calendar.close_submit') }}</button>
                        <button v-if="form.from" type="button" class="rounded-full border border-line px-4 py-2.5 text-sm font-medium" @click="clearPick">{{ $t('calendar.clear') }}</button>
                    </div>
                </form>
            </div>

            <!-- Coming up: bookings and closed days together. -->
            <section class="flex min-w-0 flex-col gap-3">
                <h2 class="font-display text-xl font-semibold">{{ $t('calendar.upcoming') }}</h2>
                <p v-if="!upcoming.length" class="rounded-2xl border border-dashed border-line p-6 text-center text-sm text-ink-muted">{{ $t('calendar.upcoming_empty') }}</p>
                <ul v-else class="flex flex-col divide-y divide-line overflow-hidden rounded-2xl border border-line bg-surface-raised">
                    <li v-for="entry in upcoming" :key="entry.key" class="flex min-w-0 flex-wrap items-center gap-x-4 gap-y-1 px-4 py-3 text-sm sm:flex-nowrap">
                        <span :class="['flex size-9 shrink-0 items-center justify-center rounded-full text-base', entry.kind === 'booked' ? 'bg-emerald-50' : 'bg-surface-muted']" aria-hidden="true">{{ entry.kind === 'booked' ? '💍' : '⛔' }}</span>
                        <div class="min-w-0 flex-1">
                            <p class="font-medium">{{ entry.label }}</p>
                            <p class="truncate text-xs text-ink-muted">
                                <template v-if="entry.kind === 'booked'">{{ entry.customer }} · {{ entry.reference }}</template>
                                <template v-else>
                                    {{ entry.reason || $t('calendar.closed_no_reason') }}
                                    <span v-if="entry.slots"> · {{ $t('calendar.slots_taken', { count: entry.slots, capacity: calendar.capacity }) }}</span>
                                </template>
                            </p>
                        </div>
                        <template v-if="entry.kind === 'booked'">
                            <UiBadge :label="entry.status_label" :tone="entry.status_tone" />
                            <a :href="entry.url" class="shrink-0 text-xs font-medium text-brand-700 hover:underline">{{ $t('calendar.open_booking') }}</a>
                        </template>
                        <span v-else-if="entry.imported" class="shrink-0 rounded-full bg-sky-50 px-2.5 py-1 text-xs text-sky-800">{{ $t('calendar.from_google') }}</span>
                        <form v-else-if="entry.destroy_url" :action="entry.destroy_url" method="POST" class="shrink-0" @submit="remember('kalendar')">
                            <input type="hidden" name="_token" :value="csrf">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="text-xs font-medium text-brand-700 hover:underline">{{ $t('calendar.reopen') }}</button>
                        </form>
                    </li>
                </ul>
            </section>
        </div>

        <!-- ============ Booking rules ============ -->
        <form v-show="tab === 'tempahan'" :action="rules.url" method="POST" class="flex max-w-3xl min-w-0 flex-col gap-6" @submit="remember('tempahan')">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">

            <label :class="[card, 'cursor-pointer flex-row items-center justify-between gap-4']">
                <span class="min-w-0">
                    <span class="block font-semibold">{{ $t('calendar.enabled') }}</span>
                    <span class="mt-0.5 block text-xs text-ink-muted">{{ $t('calendar.enabled_help') }}</span>
                </span>
                <input type="hidden" name="enabled" value="0">
                <input v-model="rules.enabled" type="checkbox" name="enabled" value="1" class="peer sr-only">
                <span class="relative h-7 w-12 shrink-0 rounded-full bg-line transition peer-checked:bg-brand-600 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400 after:absolute after:top-1 after:left-1 after:size-5 after:rounded-full after:bg-white after:shadow after:transition peer-checked:after:translate-x-5" aria-hidden="true"></span>
            </label>

            <section :class="card">
                <div>
                    <h2 class="font-semibold">{{ $t('calendar.deposit_title') }}</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $t('calendar.deposit_example', { price: money(rules.examplePrice), deposit: money(exampleDeposit) }) }}</p>
                </div>
                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <div class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.deposit_type') }}</span>
                        <div class="grid grid-cols-2 gap-1 rounded-xl bg-surface-muted p-1">
                            <label v-for="type in rules.depositTypes" :key="type.value" class="cursor-pointer">
                                <input v-model="rules.deposit_type" type="radio" name="deposit_type" :value="type.value" class="peer sr-only">
                                <span class="block rounded-lg px-3 py-2 text-center text-sm font-medium text-ink-muted transition peer-checked:bg-surface-raised peer-checked:text-ink peer-checked:shadow-sm">{{ type.label }}</span>
                            </label>
                        </div>
                        <span v-if="errors.deposit_type" class="text-xs text-brand-700">{{ errors.deposit_type }}</span>
                        <span v-else-if="rules.perPax" class="text-xs text-ink-muted">{{ $t('calendar.pax_fixed') }}</span>
                    </div>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ rules.deposit_type === 'percent' ? $t('calendar.deposit_percent') : $t('calendar.deposit_amount') }}</span>
                        <input v-model="rules.deposit_value" type="number" name="deposit_value" step="0.01" min="1" :max="rules.deposit_type === 'percent' ? 100 : undefined" required :class="input">
                        <span v-if="errors.deposit_value" class="text-xs text-brand-700">{{ errors.deposit_value }}</span>
                    </label>
                </div>
            </section>

            <section :class="card">
                <div>
                    <h2 class="font-semibold">{{ $t('calendar.days_title') }}</h2>
                    <p class="mt-0.5 text-xs text-ink-muted">{{ $t('calendar.weekdays_help') }}</p>
                </div>
                <div class="grid grid-cols-7 gap-1.5">
                    <label v-for="day in rules.weekdayOptions" :key="day.value" class="cursor-pointer" :title="day.full">
                        <input type="checkbox" name="available_weekdays[]" :value="day.value" :checked="rules.weekdays.includes(day.value)" class="peer sr-only" @change="toggleDay(day.value)">
                        <span class="block rounded-xl border border-line py-2.5 text-center text-sm font-medium text-ink-muted transition peer-checked:border-brand-500 peer-checked:bg-brand-50 peer-checked:text-brand-800 peer-focus-visible:ring-2 peer-focus-visible:ring-brand-400">{{ day.label }}</span>
                    </label>
                </div>
                <span v-if="errors.available_weekdays" class="-mt-3 text-xs text-brand-700">{{ errors.available_weekdays }}</span>

                <div class="grid min-w-0 gap-4 sm:grid-cols-3">
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.max_per_day') }}</span>
                        <input v-model="rules.max_per_day" type="number" name="max_per_day" min="1" :max="rules.maxPerDay" required :class="input">
                        <span class="text-xs text-ink-muted">{{ $t('calendar.max_per_day_help') }}</span>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.min_lead_days') }}</span>
                        <input v-model="rules.min_lead_days" type="number" name="min_lead_days" min="1" max="365" required :class="input">
                        <span class="text-xs text-ink-muted">{{ $t('calendar.min_lead_days_help') }}</span>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.max_advance_months') }}</span>
                        <input v-model="rules.max_advance_months" type="number" name="max_advance_months" min="1" max="36" required :class="input">
                        <span class="text-xs text-ink-muted">{{ $t('calendar.max_advance_months_help') }}</span>
                    </label>
                </div>
            </section>

            <section :class="card">
                <h2 class="font-semibold">{{ $t('calendar.terms_title') }}</h2>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-xs font-medium">{{ $t('calendar.deposit_terms') }}</span>
                    <textarea v-model="rules.deposit_terms" name="deposit_terms" rows="4" maxlength="3000" :placeholder="$t('calendar.deposit_terms_placeholder')" :class="input"></textarea>
                    <span class="text-xs text-ink-muted">{{ $t('calendar.deposit_terms_help') }}</span>
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-xs font-medium">{{ $t('calendar.manual_instructions') }}</span>
                    <textarea v-model="rules.manual_instructions" name="manual_instructions" rows="3" maxlength="1000" :placeholder="$t('calendar.manual_instructions_placeholder')" :class="input"></textarea>
                    <span class="text-xs text-ink-muted">{{ $t('calendar.manual_instructions_help') }}</span>
                </label>
            </section>

            <div class="sticky bottom-4 z-10 flex justify-end">
                <button type="submit" class="rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-brand-900/20 transition hover:bg-brand-700">{{ $t('calendar.save_rules') }}</button>
            </div>
        </form>

        <!-- ============ Deposit ============ -->
        <section v-show="tab === 'deposit'" :class="[card, 'max-w-3xl']">
            <div>
                <h2 class="font-semibold">{{ $t('calendar.herepay_title') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $t('calendar.herepay_intro') }}</p>
            </div>

            <div v-if="deposit.connected" class="flex flex-col gap-3 rounded-xl bg-emerald-50 p-4 text-sm sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <p class="font-semibold text-emerald-900">{{ $t('calendar.herepay_connected', { environment: deposit.environment }) }}</p>
                    <p class="mt-0.5 text-emerald-900/80">{{ deposit.verified ? $t('calendar.herepay_verified', { date: deposit.verified }) : $t('calendar.herepay_unverified') }}</p>
                </div>
                <form :action="deposit.disconnectUrl" method="POST" class="shrink-0" @submit="remember('deposit')">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="rounded-full border border-emerald-300 bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('calendar.disconnect') }}</button>
                </form>
            </div>

            <form v-else :action="deposit.connectUrl" method="POST" class="flex flex-col gap-4" @submit="remember('deposit')">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">
                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.herepay_secret') }}</span>
                        <input type="password" name="herepay_secret_key" autocomplete="off" required :class="input">
                        <span v-if="errors.herepay_secret_key" class="text-xs text-brand-700">{{ errors.herepay_secret_key }}</span>
                    </label>
                    <label class="flex min-w-0 flex-col gap-1.5">
                        <span class="text-xs font-medium">{{ $t('calendar.herepay_private') }}</span>
                        <input type="password" name="herepay_private_key" autocomplete="off" required :class="input">
                    </label>
                </div>
                <p class="text-xs text-ink-muted">{{ $t('calendar.herepay_test_note', { environment: deposit.environment }) }}</p>
                <button type="submit" class="self-start rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('calendar.herepay_connect') }}</button>
            </form>

            <p :class="['rounded-xl px-4 py-3 text-sm', deposit.channel ? 'bg-surface-muted text-ink-muted' : 'bg-amber-50 text-amber-900']">{{ $t(`calendar.channel_${deposit.channel || 'none'}`) }}</p>
        </section>

        <!-- ============ Google Calendar ============ -->
        <section v-show="tab === 'google'" :class="[card, 'max-w-3xl']">
            <div>
                <h2 class="font-semibold">{{ $t('calendar.ical_title') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $t('calendar.ical_intro') }}</p>
            </div>

            <div v-if="ical.connected" class="flex flex-col gap-3 rounded-xl bg-sky-50 p-4 text-sm">
                <p class="font-semibold break-all text-sky-900">{{ ical.masked }}</p>
                <p v-if="ical.error || ical.synced" class="text-sky-900/80">{{ ical.error ? $t('calendar.ical_last_error', { error: ical.error }) : $t('calendar.ical_synced_ago', { ago: ical.synced }) }}</p>
                <div class="flex flex-wrap gap-2">
                    <form :action="ical.syncUrl" method="POST" @submit="remember('google')">
                        <input type="hidden" name="_token" :value="csrf">
                        <button type="submit" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('calendar.ical_sync_now') }}</button>
                    </form>
                    <form :action="ical.disconnectUrl" method="POST" @submit="remember('google')">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="rounded-full border border-sky-300 bg-surface-raised px-4 py-2 text-sm font-medium transition hover:border-brand-400">{{ $t('calendar.disconnect') }}</button>
                    </form>
                </div>
            </div>

            <form :action="ical.connectUrl" method="POST" class="flex flex-col gap-3" @submit="remember('google')">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-xs font-medium">{{ ical.connected ? $t('calendar.ical_replace') : $t('calendar.ical_url') }}</span>
                    <input type="url" name="ical_url" required placeholder="https://calendar.google.com/calendar/ical/…/basic.ics" :class="input">
                    <span v-if="errors.ical_url" class="text-xs text-brand-700">{{ errors.ical_url }}</span>
                    <span v-else class="text-xs text-ink-muted">{{ $t('calendar.ical_help') }}</span>
                </label>
                <button type="submit" class="self-start rounded-full border border-brand-600 px-5 py-2.5 text-sm font-semibold text-brand-700 transition hover:bg-brand-50">{{ $t('calendar.ical_connect') }}</button>
            </form>
        </section>
    </div>
</template>
