<script setup>
/**
 * The wedding project itself: date, place and budget everything else reads.
 *
 * For a new couple this is the first thing they fill in, so it is laid out as
 * four short steps, each saying what the answer is used for, beside a list of
 * what Neekah sets up the moment they press the button. Editing an existing
 * wedding shows the same steps and, instead, what a change does and does not move.
 */
import { computed, ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiFlagSelect from '../ui/UiFlagSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    editing: { type: Boolean, default: false },
    wedding: { type: Object, required: true },
    states: { type: Array, required: true },
    /** [{ name, icon, share }] — how a new wedding's budget will be split. */
    budgetShares: { type: Array, default: () => [] },
    budgetUrl: { type: String, default: null },
    minDate: { type: String, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const form = ref({ ...props.wedding });

/** The same line icons as the dashboard sidebar (x-nav-icon). */
const icons = {
    check: '<path d="M4 5h16v15H4z"/><path d="m8 12 3 3 5-6"/>',
    wallet: '<path d="M3 7h15a3 3 0 0 1 3 3v7a3 3 0 0 1-3 3H6a3 3 0 0 1-3-3V7Z"/><path d="M3 7a2 2 0 0 1 2-2h11"/><path d="M17 13h.01"/>',
    users: '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M16 5.5a3.5 3.5 0 0 1 0 7"/><path d="M17.5 14.5A6.5 6.5 0 0 1 21.5 20"/>',
    mail: '<path d="M3 6h18v12H3z"/><path d="m3 7 9 6 9-6"/>',
    calendar: '<path d="M4 6h16v14H4z"/><path d="M4 10h16M9 3v4M15 3v4"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    rings: '<circle cx="9" cy="14" r="5.5"/><circle cx="16" cy="14" r="5.5"/><path d="m9 5 1.5 2.5h-3L9 5Z"/>',
    camera: '<path d="M4 8h3l2-3h6l2 3h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1Z"/><circle cx="12" cy="13.5" r="3.5"/>',
};

/** What a new wedding gets, in the order a couple will meet it. */
const nextSteps = [
    { icon: 'check', key: 'checklist' },
    { icon: 'wallet', key: 'budget' },
    { icon: 'users', key: 'guests' },
    { icon: 'mail', key: 'card' },
    { icon: 'calendar', key: 'timeline' },
    { icon: 'search', key: 'vendors' },
    { icon: 'rings', key: 'partner' },
];

/** What changing an existing wedding does, and what it leaves alone. */
const editNotes = [
    { icon: 'check', key: 'date' },
    { icon: 'wallet', key: 'budget' },
    { icon: 'camera', key: 'kenangan' },
];

const budgetPresets = [15000, 30000, 50000, 80000];

const locale = document.documentElement.lang || 'ms';
const money = (value) => `RM${Math.round(value).toLocaleString('en-MY')}`;

const budgetAmount = computed(() => {
    const value = Number(form.value.budget);

    return Number.isFinite(value) && value > 0 ? value : 0;
});

/** The first few categories the budget is spread over, with what each gets. */
const budgetPreview = computed(() => props.budgetShares.slice(0, 6).map((row) => ({
    ...row,
    percent: Math.round(row.share * 100),
    amount: money(budgetAmount.value * row.share),
})));

/** "Sabtu, 20 Disember 2027 · 450 hari lagi", as the date is picked. */
const dateSummary = computed(() => {
    if (!form.value.event_date) return null;

    const picked = new Date(`${form.value.event_date}T00:00:00`);

    if (Number.isNaN(picked.getTime())) return null;

    const today = new Date();
    today.setHours(0, 0, 0, 0);
    const days = Math.round((picked - today) / 86400000);

    return {
        label: new Intl.DateTimeFormat(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(picked),
        days,
    };
});

const sectionClass = 'flex min-w-0 flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-5 shadow-sm sm:p-7';
</script>

<template>
    <div class="grid min-w-0 gap-6 break-words lg:grid-cols-[minmax(0,1fr)_340px] lg:items-start xl:grid-cols-[minmax(0,1fr)_380px]">
        <form :action="action" method="POST" class="flex min-w-0 flex-col gap-5">
            <input type="hidden" name="_token" :value="csrf">
            <input v-if="editing" type="hidden" name="_method" value="PUT">

            <!-- 1. Whose wedding -->
            <section :class="sectionClass">
                <header class="flex items-start gap-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 font-display text-base font-semibold text-brand-700 ring-1 ring-brand-100">1</span>
                    <div class="min-w-0">
                        <h2 class="font-display text-xl font-semibold">{{ $t('wedding_form.step_name_title') }}</h2>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ $t('wedding_form.step_name_help') }}</p>
                    </div>
                </header>
                <UiField v-model="form.title" :label="$t('wedding_form.nama_majlis')" name="title" :placeholder="$t('wedding_form.aina_hakim')" :error="errors.title" :help="$t('wedding_form.title_help')" required maxlength="120" />
            </section>

            <!-- 2. When and where -->
            <section :class="sectionClass">
                <header class="flex items-start gap-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 font-display text-base font-semibold text-brand-700 ring-1 ring-brand-100">2</span>
                    <div class="min-w-0">
                        <h2 class="font-display text-xl font-semibold">{{ $t('wedding_form.step_when_title') }}</h2>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ $t('wedding_form.step_when_help') }}</p>
                    </div>
                </header>

                <UiField v-model="form.event_date" :label="$t('wedding_form.tarikh_majlis')" name="event_date" type="date" :min="minDate" :error="errors.event_date" :help="$t('wedding_form.date_help')" required />

                <p v-if="dateSummary && dateSummary.days > 0" class="flex flex-wrap items-center gap-x-3 gap-y-1 rounded-2xl bg-ivory px-4 py-3 text-sm ring-1 ring-gold-300/60">
                    <span class="font-display text-base font-semibold">{{ dateSummary.label }}</span>
                    <span class="rounded-full bg-gold-300/40 px-2.5 py-0.5 text-xs font-semibold text-ink">{{ $t('wedding_form.days_to_go', { count: dateSummary.days }) }}</span>
                </p>

                <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                    <UiFlagSelect v-model="form.state" :label="$t('wedding_form.negeri')" name="state" :options="states" :placeholder="$t('wedding_form.pilih_negeri')" :error="errors.state" required />
                    <UiField v-model="form.city" :label="$t('wedding_form.bandar')" name="city" :placeholder="$t('wedding_form.alor_setar')" :error="errors.city" required maxlength="80" />
                </div>
                <p class="-mt-1 text-xs text-ink-muted">{{ $t('wedding_form.place_help') }}</p>
            </section>

            <!-- 3. Budget -->
            <section :class="sectionClass">
                <header class="flex items-start gap-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 font-display text-base font-semibold text-brand-700 ring-1 ring-brand-100">3</span>
                    <div class="min-w-0">
                        <h2 class="font-display text-xl font-semibold">{{ $t('wedding_form.step_budget_title') }}</h2>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ editing ? $t('wedding_form.step_budget_help_edit') : $t('wedding_form.step_budget_help') }}</p>
                    </div>
                </header>

                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('wedding_form.bajet_rm') }}</span>
                    <span class="relative flex">
                        <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-semibold text-ink-muted">RM</span>
                        <input
                            v-model="form.budget"
                            type="number"
                            name="budget"
                            min="0"
                            step="100"
                            inputmode="numeric"
                            required
                            :class="[
                                'w-full rounded-xl border bg-surface py-2.5 pr-4 pl-12 font-display text-lg font-semibold focus:border-brand-400 focus:ring-2 focus:ring-brand-400/40 focus:outline-none',
                                errors.budget ? 'border-brand-400' : 'border-line',
                            ]"
                        >
                    </span>
                    <span v-if="errors.budget" class="text-xs text-brand-700">{{ errors.budget }}</span>
                </label>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs text-ink-muted">{{ $t('wedding_form.budget_presets') }}</span>
                    <button
                        v-for="preset in budgetPresets"
                        :key="preset"
                        type="button"
                        :class="['rounded-full border px-3 py-1.5 text-xs font-semibold transition', Number(form.budget) === preset ? 'border-brand-400 bg-brand-50 text-brand-700' : 'border-line hover:border-brand-300']"
                        @click="form.budget = preset"
                    >{{ money(preset) }}</button>
                </div>

                <!-- What the number turns into on the Bajet page. -->
                <div v-if="budgetPreview.length" class="flex flex-col gap-3 rounded-2xl bg-ivory p-4 ring-1 ring-line sm:p-5">
                    <div class="flex flex-wrap items-baseline justify-between gap-2">
                        <p class="font-display text-base font-semibold">{{ $t('wedding_form.budget_preview_title') }}</p>
                        <p class="text-xs text-ink-muted">{{ $t('wedding_form.budget_preview_help') }}</p>
                    </div>
                    <ul class="flex flex-col gap-2.5">
                        <li v-for="row in budgetPreview" :key="row.name" class="grid min-w-0 grid-cols-[1.5rem_minmax(0,1fr)_auto] items-center gap-x-3 gap-y-1 text-sm">
                            <span class="text-center" aria-hidden="true">{{ row.icon }}</span>
                            <span class="min-w-0 truncate">{{ row.name }} <span class="text-xs text-ink-muted">· {{ row.percent }}%</span></span>
                            <span class="font-semibold tabular-nums">{{ row.amount }}</span>
                            <span class="col-start-2 col-end-4 h-1.5 overflow-hidden rounded-full bg-line/70">
                                <span class="block h-full rounded-full bg-linear-to-r from-brand-400 to-gold-400" :style="{ width: `${Math.min(100, row.percent * 3)}%` }"></span>
                            </span>
                        </li>
                    </ul>
                    <p class="text-xs text-ink-muted">{{ $t('wedding_form.budget_preview_rest') }}</p>
                </div>

                <p v-if="editing && budgetUrl" class="text-xs text-ink-muted">
                    {{ $t('wedding_form.budget_edit_note') }}
                    <a :href="budgetUrl" class="font-medium text-brand-700 hover:underline">{{ $t('wedding_form.budget_edit_link') }} →</a>
                </p>
            </section>

            <!-- 4. Notes -->
            <section :class="sectionClass">
                <header class="flex items-start gap-4">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-brand-50 font-display text-base font-semibold text-brand-700 ring-1 ring-brand-100">4</span>
                    <div class="min-w-0">
                        <h2 class="font-display text-xl font-semibold">{{ $t('wedding_form.step_notes_title') }} <span class="text-sm font-normal text-ink-muted">· {{ $t('wedding_form.pilihan') }}</span></h2>
                        <p class="mt-0.5 text-sm text-ink-muted">{{ $t('wedding_form.step_notes_help') }}</p>
                    </div>
                </header>
                <UiTextarea v-model="form.notes" :label="$t('wedding_form.nota_pilihan')" name="notes" :rows="3" :placeholder="$t('wedding_form.tema_jumlah_tetamu_permintaan_khas')" :error="errors.notes" />
            </section>

            <div class="flex flex-col gap-3 rounded-3xl border border-gold-300/70 bg-surface-raised p-5 shadow-sm sm:flex-row sm:items-center sm:justify-between sm:p-6">
                <p class="text-sm text-ink-muted">{{ editing ? $t('wedding_form.submit_note_edit') : $t('wedding_form.submit_note') }}</p>
                <div class="flex flex-col-reverse gap-2 sm:flex-row sm:items-center">
                    <a :href="cancelUrl" class="rounded-full px-6 py-3 text-center text-sm font-medium text-ink-muted transition hover:bg-surface-muted">{{ $t('wedding_form.batal') }}</a>
                    <button type="submit" class="inline-flex shrink-0 items-center justify-center gap-2 rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold whitespace-nowrap text-white shadow-lg shadow-brand-600/20 transition hover:bg-brand-700">
                        <svg v-if="!editing" class="size-4 text-gold-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2l1.8 5.6L19.5 9l-5.7 1.4L12 16l-1.8-5.6L4.5 9l5.7-1.4L12 2Z" /></svg>
                        {{ editing ? $t('common.simpan') : $t('wedding_form.cipta_majlis') }}
                    </button>
                </div>
            </div>
        </form>

        <!-- Beside the form: what pressing the button sets up, or what a change moves. -->
        <aside class="flex min-w-0 flex-col gap-5 rounded-3xl border border-line bg-surface-raised p-5 shadow-sm sm:p-6 lg:sticky lg:top-6">
            <div>
                <p class="font-display text-sm font-semibold text-gold-600">{{ editing ? $t('wedding_form.edit_notes_eyebrow') : $t('wedding_form.next_eyebrow') }}</p>
                <h2 class="mt-1 font-display text-xl font-semibold">{{ editing ? $t('wedding_form.edit_notes_title') : $t('wedding_form.next_title') }}</h2>
            </div>

            <ul class="flex flex-col gap-4">
                <li v-for="item in (editing ? editNotes : nextSteps)" :key="item.key" class="flex gap-3">
                    <span class="flex size-9 shrink-0 items-center justify-center rounded-xl bg-brand-50 text-brand-600">
                        <svg class="size-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" v-html="icons[item.icon]"></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold">{{ $t(`wedding_form.${editing ? 'edit' : 'next'}_${item.key}_title`) }}</p>
                        <p class="text-xs leading-relaxed text-ink-muted">{{ $t(`wedding_form.${editing ? 'edit' : 'next'}_${item.key}_body`) }}</p>
                    </div>
                </li>
            </ul>

            <p v-if="!editing" class="rounded-2xl bg-ivory px-4 py-3 text-xs text-ink-muted ring-1 ring-line">{{ $t('wedding_form.free_note') }}</p>
        </aside>
    </div>
</template>
