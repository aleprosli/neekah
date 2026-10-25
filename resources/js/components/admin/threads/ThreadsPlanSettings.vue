<script setup>
/**
 * The daily plan: a list of times, each with the kind of post it gets, plus
 * the switch that lets anything go out at all. Saving with the switch on
 * fills the new slots straight away.
 */
import { ref } from 'vue';

const props = defineProps({
    settings: { type: Object, required: true },
    types: { type: Array, required: true },
    limits: { type: Object, required: true },
    connected: { type: Boolean, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const enabled = ref(props.settings.enabled);
const slots = ref(props.settings.slots.map((slot) => ({ ...slot })));

const addSlot = () => slots.value.push({ time: '12:00', type: 'listing' });
const removeSlot = (index) => slots.value.splice(index, 1);

const inputClass = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none';
</script>

<template>
    <form :action="settings.update_url" method="POST" class="flex max-w-3xl min-w-0 flex-col gap-6">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="PUT">

        <section class="rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <label class="flex items-start gap-3">
                <input type="hidden" name="enabled" value="0">
                <input v-model="enabled" type="checkbox" name="enabled" value="1" :disabled="!connected && !enabled" class="mt-1 accent-brand-600">
                <span>
                    <span class="block text-sm font-semibold">{{ $t('admin_threads.hidupkan_auto_post') }}</span>
                    <span class="block text-xs text-ink-muted">{{ connected ? $t('admin_threads.hidupkan_bantuan') : $t('admin_threads.sambung_dahulu') }}</span>
                </span>
            </label>
            <p v-if="errors.enabled" class="mt-2 text-xs text-brand-700">{{ errors.enabled }}</p>
        </section>

        <section class="flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6">
            <div>
                <h2 class="font-semibold">{{ $t('admin_threads.slot_harian') }}</h2>
                <p class="mt-1 text-sm text-ink-muted">{{ $t('admin_threads.slot_bantuan') }}</p>
            </div>

            <ul class="flex min-w-0 flex-col gap-2">
                <li v-for="(slot, index) in slots" :key="index" class="flex min-w-0 items-center gap-2">
                    <input v-model="slot.time" type="time" :name="`slots[${index}][time]`" required class="w-32 shrink-0 rounded-xl border border-line bg-surface px-3 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                    <select v-model="slot.type" :name="`slots[${index}][type]`" class="nk-select min-w-0 flex-1 rounded-xl border border-line bg-surface py-2.5 pr-10 pl-4 text-sm focus:border-brand-400 focus:outline-none">
                        <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <button type="button" class="flex size-9 shrink-0 items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-red-700 disabled:opacity-30" :disabled="slots.length === 1" :aria-label="$t('admin_threads.buang_slot')" @click="removeSlot(index)">
                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6 6 18" /></svg>
                    </button>
                </li>
            </ul>

            <p v-if="errors.slots || errors['slots.0.time']" class="text-xs text-brand-700">{{ errors.slots || errors['slots.0.time'] }}</p>

            <button v-if="slots.length < limits.slots" type="button" class="w-fit rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="addSlot">{{ $t('admin_threads.tambah_slot') }}</button>
        </section>

        <section class="grid min-w-0 gap-4 rounded-2xl border border-line bg-surface-raised p-5 sm:grid-cols-3 sm:p-6">
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('admin_threads.topik_lalai') }}</span>
                <input type="text" name="topic_tag" :value="settings.topic_tag" maxlength="50" :class="inputClass">
                <span class="text-xs text-ink-muted">{{ $t('admin_threads.topik_lalai_bantuan') }}</span>
                <span v-if="errors.topic_tag" class="text-xs text-brand-700">{{ errors.topic_tag }}</span>
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('admin_threads.hari_ke_depan') }}</span>
                <input type="number" name="days_ahead" :value="settings.days_ahead" min="1" :max="limits.days_ahead" required :class="inputClass">
                <span class="text-xs text-ink-muted">{{ $t('admin_threads.hari_ke_depan_bantuan') }}</span>
            </label>
            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="text-sm font-medium">{{ $t('admin_threads.rehat_vendor') }}</span>
                <input type="number" name="listing_cooldown_days" :value="settings.listing_cooldown_days" min="1" max="365" required :class="inputClass">
                <span class="text-xs text-ink-muted">{{ $t('admin_threads.rehat_vendor_bantuan') }}</span>
            </label>
        </section>

        <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('admin_threads.simpan_tetapan') }}</button>
    </form>
</template>
