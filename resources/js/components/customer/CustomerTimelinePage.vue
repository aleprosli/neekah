<script setup>
/**
 * The running order of the wedding day. Each slot may name a vendor, and that
 * vendor sees only their own slots on their side of the platform.
 *
 * Empty, the page says what a timeline is for, shows one finished on a phone
 * (a mockup, not data), and starts it from a ready-made running order in one
 * tap. Filled, it is the day top to bottom, with adding a slot folded away
 * until it is wanted.
 */
import { ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';

const props = defineProps({
    items: { type: Array, required: true },
    vendors: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    templateUrl: { type: String, required: true },
    templates: { type: Array, default: () => [] },
    eventDate: { type: String, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

/** The form opens on its own when it came back with errors, or once there is nothing to show instead. */
const adding = ref(Object.keys(props.errors).some((key) => key !== 'template'));

/** What a finished day looks like, for the mockup only. */
const sample = [
    { time: '8:00 AM', title: 'timeline.sample_makeup', vendor: 'timeline.sample_v_makeup', icon: '💄' },
    { time: '9:00 AM', title: 'timeline.sample_akad', vendor: 'timeline.sample_v_photo', icon: '📸' },
    { time: '11:00 AM', title: 'timeline.sample_guests', vendor: 'timeline.sample_v_catering', icon: '🍛' },
    { time: '12:30 PM', title: 'timeline.sample_arrive', vendor: 'timeline.sample_v_kompang', icon: '🥁' },
    { time: '12:45 PM', title: 'timeline.sample_bersanding', vendor: null, icon: null },
];

const input = 'w-full min-w-0 rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none';
</script>

<template>
    <!-- Empty: what it is for, a finished one, and a one-tap start -->
    <section v-if="!items.length" class="grid items-center gap-8 lg:grid-cols-[minmax(0,1fr)_minmax(0,22rem)]">
        <div class="flex min-w-0 flex-col gap-6">
            <div>
                <p class="font-script text-3xl text-gold-600">{{ $t('timeline.intro_eyebrow') }}</p>
                <h2 class="mt-1 font-display text-3xl font-semibold tracking-tight">{{ $t('timeline.intro_title') }}</h2>
                <ul class="mt-4 flex flex-col gap-2.5 text-sm text-ink-muted">
                    <li v-for="point in ['timeline.intro_point_order', 'timeline.intro_point_vendor', 'timeline.intro_point_day']" :key="point" class="flex gap-3">
                        <span class="mt-0.5 flex size-5 shrink-0 items-center justify-center rounded-full bg-emerald-500 text-[11px] font-bold text-white" aria-hidden="true">✓</span>
                        <span>{{ $t(point) }}</span>
                    </li>
                </ul>
            </div>

            <div>
                <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $t('timeline.pick_template') }}</p>
                <div class="mt-3 grid gap-3 sm:grid-cols-3">
                    <form v-for="template in templates" :key="template.key" :action="templateUrl" method="POST">
                        <input type="hidden" name="_token" :value="csrf">
                        <input type="hidden" name="template" :value="template.key">
                        <button type="submit" class="group flex h-full w-full flex-col gap-1 rounded-2xl bg-surface-raised p-4 text-left ring-1 ring-line transition hover:-translate-y-0.5 hover:ring-brand-300">
                            <span class="font-semibold">{{ template.title }}</span>
                            <span class="text-xs text-ink-muted">{{ template.body }}</span>
                            <span class="mt-auto pt-2 text-xs font-semibold text-brand-700">{{ $t('timeline.use_template', { count: template.count }) }} →</span>
                        </button>
                    </form>
                </div>
                <p v-if="errors.template" class="mt-2 text-xs text-brand-700">{{ errors.template }}</p>
                <button type="button" class="mt-3 text-sm font-medium text-ink-muted underline underline-offset-4 hover:text-ink" @click="adding = !adding">{{ $t('timeline.start_blank') }}</button>
            </div>
        </div>

        <!-- The mockup: a phone showing a finished day -->
        <div class="relative mx-auto w-full max-w-[19rem]" aria-hidden="true">
            <div class="pointer-events-none absolute -inset-6 rounded-full bg-gold-300/30 blur-3xl"></div>
            <div class="relative rounded-[2.5rem] bg-ink p-2.5 shadow-2xl shadow-brand-900/30">
                <div class="overflow-hidden rounded-[2rem] bg-ivory">
                    <div class="bg-linear-to-br from-brand-700 to-brand-900 px-5 pt-6 pb-5 text-white">
                        <p class="font-script text-xl text-gold-300">{{ $t('timeline.sample_heading') }}</p>
                        <p class="text-xs text-white/75">{{ eventDate }}</p>
                    </div>
                    <ol class="relative flex flex-col gap-3 px-4 py-4">
                        <span class="absolute top-6 bottom-6 left-[1.6rem] w-px bg-gold-400/60"></span>
                        <li v-for="row in sample" :key="row.time" class="relative flex gap-3">
                            <span class="relative z-10 mt-1 size-2.5 shrink-0 rounded-full bg-brand-600 ring-4 ring-ivory"></span>
                            <span class="min-w-0 flex-1 rounded-xl bg-white px-3 py-2 shadow-sm">
                                <span class="block text-[10px] font-semibold text-brand-700">{{ row.time }}</span>
                                <span class="block text-xs font-semibold">{{ $t(row.title) }}</span>
                                <span v-if="row.vendor" class="mt-1 inline-block rounded-full bg-gold-300/30 px-2 py-0.5 text-[10px] text-ink-muted">{{ row.icon }} {{ $t(row.vendor) }}</span>
                            </span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <!-- Filled: the day, top to bottom -->
    <template v-else>
        <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
            <p class="text-sm text-ink-muted">{{ $t('timeline.count', { count: items.length }) }}</p>
            <button type="button" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700" @click="adding = !adding">{{ adding ? $t('timeline.close_form') : `+ ${$t('timeline.tambah_aktiviti')}` }}</button>
        </div>

        <ol class="relative flex flex-col gap-4">
            <span class="absolute top-3 bottom-3 left-[5.35rem] w-px bg-gold-400/60 sm:left-[7.85rem]" aria-hidden="true"></span>
            <li v-for="item in items" :key="item.id" class="relative flex gap-4 sm:gap-6">
                <div class="w-16 shrink-0 pt-3 text-right sm:w-24">
                    <p class="font-display text-base font-semibold sm:text-lg">{{ item.starts_at }}</p>
                    <p v-if="item.ends_at" class="text-[11px] text-ink-muted">{{ $t('timeline.until', { time: item.ends_at }) }}</p>
                </div>
                <span class="relative z-10 mt-4 size-3 shrink-0 rounded-full bg-brand-600 ring-4 ring-ivory" aria-hidden="true"></span>
                <div class="flex min-w-0 flex-1 items-start gap-3 rounded-2xl bg-surface-raised p-4 ring-1 ring-line">
                    <div class="min-w-0 flex-1">
                        <p class="font-semibold break-words">{{ item.title }}</p>
                        <p class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-ink-muted">
                            <a v-if="item.vendor" :href="item.vendor.url" class="inline-flex items-center gap-1.5 rounded-full bg-gold-300/25 px-2.5 py-0.5 text-xs font-medium text-ink hover:bg-gold-300/40">
                                <img v-if="item.vendor.illustration" :src="item.vendor.illustration" alt="" class="size-4 object-contain mix-blend-multiply">
                                <span v-else aria-hidden="true">{{ item.vendor.icon }}</span>
                                {{ item.vendor.name }}
                            </a>
                            <span v-if="item.location" class="break-words">📍 {{ item.location }}</span>
                        </p>
                        <p v-if="item.notes" class="mt-1.5 text-sm break-words text-ink-muted">{{ item.notes }}</p>
                    </div>
                    <UiConfirm
                        :action="item.destroy_url"
                        method="DELETE"
                        tone="danger"
                        :title="$t('timeline.padam_aktiviti_ini')"
                        :message="`${item.starts_at} · ${item.title}`"
                        :confirm-label="$t('timeline.padam')"
                        trigger-class="shrink-0 text-xs font-medium text-ink-muted hover:text-brand-700"
                        :csrf="csrf"
                    >{{ $t('timeline.padam_2') }}</UiConfirm>
                </div>
            </li>
        </ol>
    </template>

    <!-- Adding a slot, folded away until it is wanted -->
    <form v-if="adding" :action="storeUrl" method="POST" class="mt-8 flex flex-col gap-4 rounded-3xl bg-surface-raised p-5 ring-1 ring-line sm:p-6">
        <input type="hidden" name="_token" :value="csrf">
        <p class="font-display text-lg font-semibold">{{ $t('timeline.tambah_aktiviti') }}</p>

        <p v-if="!vendors.length" class="rounded-2xl bg-surface-muted p-3 text-xs text-ink-muted">{{ $t('timeline.tempah_vendor_dahulu_untuk_menugaskan') }}</p>

        <div class="grid grid-cols-2 gap-3 sm:grid-cols-[7rem_7rem_minmax(0,1fr)]">
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.mula') }}</span>
                <input type="time" name="starts_at" required :class="input">
            </label>
            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.tamat') }}</span>
                <input type="time" name="ends_at" :class="input">
            </label>
            <label class="col-span-2 flex min-w-0 flex-col gap-1.5 sm:col-span-1">
                <span class="text-sm font-medium">{{ $t('timeline.aktiviti') }}</span>
                <input type="text" name="title" :placeholder="$t('timeline.contoh_akad_nikah')" required :class="input">
            </label>
        </div>

        <div class="grid gap-3 sm:grid-cols-3">
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.vendor_terlibat') }}</span>
                <select name="vendor_id" class="nk-select rounded-xl border border-line bg-surface px-3 py-2.5 pr-9 text-sm focus:border-brand-400 focus:outline-none">
                    <option value="">{{ $t('timeline.tiada') }}</option>
                    <option v-for="vendor in vendors" :key="vendor.id" :value="vendor.id">{{ vendor.icon }} {{ vendor.name }}</option>
                </select>
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.lokasi') }}</span>
                <input type="text" name="location" :placeholder="$t('timeline.rumah_pengantin_dewan')" :class="input">
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('timeline.nota') }}</span>
                <input type="text" name="notes" :placeholder="$t('timeline.arahan_untuk_vendor')" :class="input">
            </label>
        </div>

        <p v-for="(message, key) in errors" v-show="key !== 'template'" :key="key" class="text-xs text-brand-700">{{ message }}</p>

        <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('timeline.tambah_aktiviti') }}</button>
    </form>
</template>
