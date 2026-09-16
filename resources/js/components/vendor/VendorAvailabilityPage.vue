<script setup>
/**
 * Dates the vendor is closed, and the dates already taken by a booking.
 *
 * Closing a date is an ordinary form post; a month grid sits beside it so the
 * vendor can see the two kinds of unavailable date next to each other instead
 * of reading them off two lists.
 */
import { computed, ref } from 'vue';
import UiBadge from '../ui/UiBadge.vue';
import UiField from '../ui/UiField.vue';

const props = defineProps({
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    closed: { type: Array, required: true },
    booked: { type: Array, required: true },
    today: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const form = ref({ from: props.today, to: '', reason: '' });
const monthOffset = ref(0);

const closedByDate = computed(() => Object.fromEntries(props.closed.map((date) => [date.date, date])));
const bookedByDate = computed(() => Object.fromEntries(props.booked.map((booking) => [booking.date, booking])));

/** The month being shown, as a grid that starts on Monday. */
const month = computed(() => {
    const start = new Date(props.today);
    const cursor = new Date(start.getFullYear(), start.getMonth() + monthOffset.value, 1);
    const days = [];

    // Pad to the first Monday so the columns line up with the weekday labels.
    const lead = (cursor.getDay() + 6) % 7;
    for (let i = 0; i < lead; i++) days.push(null);

    const last = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 0).getDate();
    for (let day = 1; day <= last; day++) {
        const date = new Date(cursor.getFullYear(), cursor.getMonth(), day);
        days.push({
            day,
            iso: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`,
        });
    }

    return {
        label: cursor.toLocaleDateString('ms-MY', { month: 'long', year: 'numeric' }),
        days,
    };
});

const stateOf = (iso) => {
    if (bookedByDate.value[iso]) return 'booked';
    if (closedByDate.value[iso]) return 'closed';
    return iso < props.today ? 'past' : 'open';
};

const pick = (iso) => {
    if (stateOf(iso) !== 'open') return;
    form.value.from = iso;
};
</script>

<template>
    <div class="grid gap-8 lg:grid-cols-2">
        <section class="flex flex-col gap-4">
            <form :action="storeUrl" method="POST" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
                <input type="hidden" name="_token" :value="csrf">
                <h2 class="font-semibold">Tutup tarikh</h2>

                <div class="grid gap-4 sm:grid-cols-2">
                    <UiField v-model="form.from" label="Dari" name="from" type="date" :min="today" :error="errors.from" required />
                    <UiField v-model="form.to" label="Hingga (pilihan)" name="to" type="date" :min="form.from" :error="errors.to" help="Kosongkan untuk satu hari sahaja." />
                </div>

                <UiField v-model="form.reason" label="Sebab (pilihan)" name="reason" placeholder="Cuti, majlis luar platform, dll." :error="errors.reason" />

                <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tutup tarikh</button>
            </form>

            <div class="rounded-2xl border border-line">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Tarikh ditutup</h2>
                <p v-if="!closed.length" class="px-5 py-6 text-sm text-ink-muted">Tiada tarikh ditutup.</p>
                <ul v-else class="divide-y divide-line">
                    <li v-for="date in closed" :key="date.id" class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 text-sm">
                        <span class="font-medium">{{ date.label }}</span>
                        <span class="truncate text-ink-muted">{{ date.reason }}</span>
                        <form :action="date.destroy_url" method="POST" class="ml-auto">
                            <input type="hidden" name="_token" :value="csrf">
                            <input type="hidden" name="_method" value="DELETE">
                            <button type="submit" class="text-xs font-medium text-brand-600 hover:underline">Buka semula</button>
                        </form>
                    </li>
                </ul>
            </div>
        </section>

        <section class="flex flex-col gap-4">
            <div class="rounded-2xl border border-line bg-surface-raised p-5">
                <div class="flex items-center justify-between gap-3">
                    <button type="button" class="rounded-full px-3 py-1 text-lg leading-none text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="monthOffset <= 0" aria-label="Bulan sebelum" @click="monthOffset--">‹</button>
                    <p class="font-display text-lg font-semibold">{{ month.label }}</p>
                    <button type="button" class="rounded-full px-3 py-1 text-lg leading-none text-ink-muted transition hover:bg-surface-muted" aria-label="Bulan seterusnya" @click="monthOffset++">›</button>
                </div>

                <div class="mt-4 grid grid-cols-7 gap-1 text-center text-[11px] text-ink-muted">
                    <span v-for="label in ['I', 'S', 'R', 'K', 'J', 'S', 'A']" :key="label">{{ label }}</span>
                </div>

                <div class="mt-1 grid grid-cols-7 gap-1">
                    <template v-for="(cell, at) in month.days" :key="at">
                        <span v-if="!cell"></span>
                        <button
                            v-else
                            type="button"
                            :disabled="stateOf(cell.iso) !== 'open'"
                            :title="bookedByDate[cell.iso]?.reference || closedByDate[cell.iso]?.reason || ''"
                            :class="[
                                'aspect-square rounded-lg text-sm transition',
                                stateOf(cell.iso) === 'booked' ? 'bg-emerald-100 font-semibold text-emerald-800' : '',
                                stateOf(cell.iso) === 'closed' ? 'bg-surface-muted text-ink-muted line-through' : '',
                                stateOf(cell.iso) === 'past' ? 'text-ink-muted/40' : '',
                                stateOf(cell.iso) === 'open' ? 'hover:bg-brand-50 hover:text-brand-700' : '',
                                form.from === cell.iso ? 'ring-2 ring-brand-600' : '',
                            ]"
                            @click="pick(cell.iso)"
                        >{{ cell.day }}</button>
                    </template>
                </div>

                <ul class="mt-4 flex flex-wrap gap-4 text-xs text-ink-muted">
                    <li class="flex items-center gap-1.5"><span class="size-3 rounded bg-emerald-100"></span> Ada tempahan</li>
                    <li class="flex items-center gap-1.5"><span class="size-3 rounded bg-surface-muted"></span> Ditutup</li>
                </ul>
            </div>

            <div class="rounded-2xl border border-line">
                <h2 class="border-b border-line px-5 py-3 text-sm font-semibold">Tarikh dengan tempahan</h2>
                <p v-if="!booked.length" class="px-5 py-6 text-sm text-ink-muted">Tiada tempahan akan datang.</p>
                <ul v-else class="divide-y divide-line">
                    <li v-for="booking in booked" :key="booking.reference" class="flex flex-wrap items-center gap-x-3 gap-y-1 px-5 py-3 text-sm">
                        <span class="font-medium">{{ booking.label }}</span>
                        <a :href="booking.url" class="text-ink-muted hover:text-ink">{{ booking.reference }}</a>
                        <UiBadge class="ml-auto" :label="booking.status_label" :tone="booking.status_tone" />
                    </li>
                </ul>
            </div>
        </section>
    </div>
</template>
