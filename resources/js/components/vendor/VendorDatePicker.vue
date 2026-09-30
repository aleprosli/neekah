<script setup>
/**
 * The date picker in a vendor's booking form: a month at a time, fetched from
 * the vendor's availability endpoint, with only open days pickable. It posts
 * the chosen day through a hidden input named `name`, so the surrounding
 * Blade form works unchanged; the server checks the day again on submit.
 */
import { computed, onMounted, ref } from 'vue';

const props = defineProps({
    availabilityUrl: { type: String, required: true },
    name: { type: String, default: 'event_date' },
    value: { type: String, default: '' },
    today: { type: String, required: true },
});

const selected = ref(props.value);
const cursor = ref(props.value ? props.value.slice(0, 7) : props.today.slice(0, 7));
const months = ref({});
const loading = ref(false);
const failed = ref(false);

const locale = document.documentElement.lang === 'en' ? 'en-MY' : 'ms-MY';

const load = async (month) => {
    if (months.value[month]) return;

    loading.value = true;
    failed.value = false;

    try {
        const url = new URL(props.availabilityUrl, window.location.origin);
        url.searchParams.set('bulan', month);
        const response = await fetch(url, { headers: { Accept: 'application/json' } });

        if (!response.ok) throw new Error(String(response.status));

        months.value[month] = (await response.json()).days;
    } catch {
        failed.value = true;
    } finally {
        loading.value = false;
    }
};

const shift = (step) => {
    const [year, month] = cursor.value.split('-').map(Number);
    const next = new Date(year, month - 1 + step, 1);
    cursor.value = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}`;
    load(cursor.value);
};

const grid = computed(() => {
    const [year, month] = cursor.value.split('-').map(Number);
    const first = new Date(year, month - 1, 1);
    const days = Array.from({ length: (first.getDay() + 6) % 7 }, () => null);
    const last = new Date(year, month, 0).getDate();

    for (let day = 1; day <= last; day++) {
        const iso = `${cursor.value}-${String(day).padStart(2, '0')}`;
        days.push({ day, iso, status: months.value[cursor.value]?.[iso]?.status ?? 'loading' });
    }

    return {
        label: first.toLocaleDateString(locale, { month: 'long', year: 'numeric' }),
        days,
    };
});

const canGoBack = computed(() => cursor.value > props.today.slice(0, 7));
const selectedLabel = computed(() => (selected.value
    ? new Date(`${selected.value}T00:00:00`).toLocaleDateString(locale, { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' })
    : ''));

const pick = (cell) => {
    if (cell.status === 'open') selected.value = cell.iso;
};

onMounted(() => load(cursor.value));
</script>

<template>
    <div class="flex flex-col gap-3">
        <input type="hidden" :name="name" :value="selected">

        <div class="flex items-center justify-between gap-2">
            <button type="button" class="rounded-full px-3 py-1 text-lg leading-none text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="!canGoBack" :aria-label="$t('date_picker.previous')" @click="shift(-1)">‹</button>
            <p class="text-sm font-semibold first-letter:uppercase">{{ grid.label }}</p>
            <button type="button" class="rounded-full px-3 py-1 text-lg leading-none text-ink-muted transition hover:bg-surface-muted" :aria-label="$t('date_picker.next')" @click="shift(1)">›</button>
        </div>

        <div class="grid grid-cols-7 gap-1 text-center text-[11px] text-ink-muted">
            <span v-for="(label, at) in $t('date_picker.weekdays').split(',')" :key="at">{{ label }}</span>
        </div>

        <div class="grid grid-cols-7 gap-1" :aria-busy="loading">
            <template v-for="(cell, at) in grid.days" :key="at">
                <span v-if="!cell"></span>
                <button
                    v-else
                    type="button"
                    :disabled="cell.status !== 'open'"
                    :aria-pressed="selected === cell.iso"
                    :title="$t(`date_picker.status_${cell.status}`)"
                    :class="[
                        'aspect-square rounded-lg text-sm transition',
                        selected === cell.iso
                            ? 'bg-brand-600 font-semibold text-white'
                            : cell.status === 'open'
                                ? 'bg-emerald-50 font-medium text-emerald-900 hover:bg-emerald-100'
                                : cell.status === 'full' || cell.status === 'closed'
                                    ? 'bg-surface-muted text-ink-muted line-through'
                                    : 'text-ink-muted/50',
                    ]"
                    @click="pick(cell)"
                >{{ cell.day }}</button>
            </template>
        </div>

        <div class="flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-ink-muted">
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded bg-emerald-100"></span>{{ $t('date_picker.status_open') }}</span>
            <span class="flex items-center gap-1.5"><span class="size-2.5 rounded bg-surface-muted"></span>{{ $t('date_picker.status_full') }}</span>
        </div>

        <p v-if="failed" class="text-xs text-brand-700">{{ $t('date_picker.failed') }}</p>
        <p v-else-if="selected" class="text-sm font-medium">{{ $t('date_picker.chosen', { date: selectedLabel }) }}</p>
        <p v-else class="text-xs text-ink-muted">{{ $t('date_picker.choose') }}</p>
    </div>
</template>
