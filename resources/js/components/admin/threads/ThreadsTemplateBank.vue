<script setup>
/**
 * The bank the planner writes from. Each type is a stack the planner goes
 * round in turn, least recently used first, so a bigger stack means the
 * account repeats itself less. Listing and blog templates carry placeholders
 * that the vendor's or the article's own values replace.
 */
import { computed, ref } from 'vue';
import UiBadge from '../../ui/UiBadge.vue';
import UiConfirm from '../../ui/UiConfirm.vue';

const props = defineProps({
    templates: { type: Array, required: true },
    types: { type: Array, required: true },
    limits: { type: Object, required: true },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const filter = ref('');
/** null = closed; {} = a new template; a row = editing it. */
const editing = ref(null);
const form = ref(null);

const visible = computed(() => props.templates.filter((template) => !filter.value || template.type === filter.value));
const countOf = (type) => props.templates.filter((template) => template.type === type && template.is_active).length;

const open = (template) => {
    editing.value = template;
    form.value = {
        type: template.type ?? (filter.value || 'engagement'),
        body: template.body ?? '',
        topic_tag: template.topic_tag ?? '',
        poll_options: [...(template.poll_options ?? []), '', '', '', ''].slice(0, 4),
        is_active: template.is_active ?? true,
    };
};

const placeholders = computed(() => props.types.find((type) => type.value === form.value?.type)?.placeholders ?? []);
const allowsPoll = computed(() => !['listing', 'blog'].includes(form.value?.type));

const encoder = new TextEncoder();
const bytes = computed(() => encoder.encode(form.value?.body ?? '').length);

const insert = (placeholder) => {
    form.value.body = `${form.value.body}${form.value.body && !form.value.body.endsWith(' ') ? ' ' : ''}${placeholder}`;
};

const inputClass = 'w-full rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none';
</script>

<template>
    <section class="flex min-w-0 flex-col gap-4">
        <div class="flex min-w-0 flex-wrap items-center gap-2">
            <div class="no-scrollbar flex min-w-0 flex-1 gap-1 overflow-x-auto">
                <button
                    type="button"
                    :class="['h-8 shrink-0 rounded-full px-3 text-xs font-semibold transition', !filter ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-surface-muted']"
                    @click="filter = ''"
                >{{ $t('admin_threads.semua') }} · {{ templates.length }}</button>
                <button
                    v-for="type in types"
                    :key="type.value"
                    type="button"
                    :class="['h-8 shrink-0 rounded-full px-3 text-xs font-semibold transition', filter === type.value ? 'bg-brand-50 text-brand-700' : 'text-ink-muted hover:bg-surface-muted']"
                    @click="filter = type.value"
                >{{ type.label }} · {{ countOf(type.value) }}</button>
            </div>
            <button type="button" class="inline-flex h-10 items-center gap-2 rounded-full bg-brand-600 px-4 text-sm font-semibold text-white transition hover:bg-brand-700" @click="open({})">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14" /></svg>
                {{ $t('admin_threads.tambah_templat') }}
            </button>
        </div>

        <p class="text-sm text-ink-muted">{{ $t('admin_threads.templat_bantuan') }}</p>

        <!-- The form, above the list so it is where the eye already is. -->
        <form v-if="editing" :action="editing.id ? editing.update_url : storeUrl" method="POST" class="flex min-w-0 flex-col gap-4 rounded-2xl border border-brand-200 bg-surface-raised p-5">
            <input type="hidden" name="_token" :value="csrf">
            <input v-if="editing.id" type="hidden" name="_method" value="PUT">

            <div class="grid min-w-0 gap-4 sm:grid-cols-2">
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('admin_threads.jenis') }}</span>
                    <select v-model="form.type" name="type" class="nk-select rounded-xl border border-line bg-surface py-2.5 pr-10 pl-4 text-sm focus:border-brand-400 focus:outline-none">
                        <option v-for="type in types" :key="type.value" :value="type.value">{{ type.label }}</option>
                    </select>
                    <span class="text-xs text-ink-muted">{{ types.find((type) => type.value === form.type)?.description }}</span>
                </label>
                <label class="flex min-w-0 flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('admin_threads.topik') }}</span>
                    <input v-model="form.topic_tag" type="text" name="topic_tag" maxlength="50" :placeholder="$t('admin_threads.topik_ikut_tetapan')" :class="inputClass">
                    <span v-if="errors.topic_tag" class="text-xs text-brand-700">{{ errors.topic_tag }}</span>
                </label>
            </div>

            <label class="flex min-w-0 flex-col gap-1.5">
                <span class="flex items-center justify-between gap-2 text-sm font-medium">
                    {{ $t('admin_threads.teks') }}
                    <span class="text-xs font-normal text-ink-muted">{{ bytes }} / {{ limits.text_bytes }}</span>
                </span>
                <textarea v-model="form.body" name="body" rows="6" required :class="inputClass"></textarea>
                <span v-if="errors.body" class="text-xs text-brand-700">{{ errors.body }}</span>
            </label>

            <div v-if="placeholders.length" class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs text-ink-muted">{{ $t('admin_threads.sisip') }}</span>
                <button v-for="placeholder in placeholders" :key="placeholder" type="button" class="rounded-full border border-line px-2.5 py-1 font-mono text-xs transition hover:border-brand-400" @click="insert(placeholder)">{{ placeholder }}</button>
            </div>

            <fieldset v-if="allowsPoll" class="flex min-w-0 flex-col gap-2">
                <legend class="text-sm font-medium">{{ $t('admin_threads.undian') }}</legend>
                <p class="text-xs text-ink-muted">{{ $t('admin_threads.undian_bantuan', { max: limits.poll_option }) }}</p>
                <div class="grid min-w-0 gap-2 sm:grid-cols-2">
                    <input
                        v-for="(option, index) in form.poll_options"
                        :key="index"
                        v-model="form.poll_options[index]"
                        type="text"
                        name="poll_options[]"
                        :maxlength="limits.poll_option"
                        :placeholder="$t('admin_threads.pilihan_n', { n: index + 1 })"
                        :class="inputClass"
                    >
                </div>
            </fieldset>

            <div class="flex flex-wrap items-center gap-3">
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="accent-brand-600">{{ $t('admin_threads.aktif') }}</label>
                <span class="flex-1"></span>
                <button type="button" class="rounded-full border border-line px-5 py-2 text-sm font-medium transition hover:border-brand-400" @click="editing = null">{{ $t('admin_threads.batal') }}</button>
                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('admin_threads.simpan') }}</button>
            </div>
        </form>

        <p v-if="!visible.length" class="rounded-2xl border border-dashed border-line px-6 py-10 text-center text-sm text-ink-muted">{{ $t('admin_threads.templat_kosong') }}</p>

        <ul class="flex min-w-0 flex-col gap-3">
            <li v-for="template in visible" :key="template.id" :class="['flex min-w-0 flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-4', template.is_active ? '' : 'opacity-60']">
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <UiBadge :label="template.type_label" :tone="template.type_tone" />
                    <UiBadge v-if="!template.is_active" :label="$t('admin_threads.tidak_aktif')" />
                    <span class="text-xs text-ink-muted">{{ template.used }}<template v-if="template.last_used"> · {{ template.last_used }}</template></span>
                </div>
                <p class="text-sm whitespace-pre-line">{{ template.body }}</p>
                <ul v-if="template.poll_options.length" class="flex flex-wrap gap-1.5">
                    <li v-for="option in template.poll_options" :key="option" class="rounded-full border border-line px-3 py-1 text-xs">{{ option }}</li>
                </ul>
                <div class="flex flex-wrap gap-2">
                    <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="open(template)">{{ $t('admin_threads.edit') }}</button>
                    <UiConfirm
                        :action="template.destroy_url"
                        method="DELETE"
                        tone="danger"
                        :title="$t('admin_threads.padam_templat_tajuk')"
                        :message="$t('admin_threads.padam_templat_mesej')"
                        :confirm-label="$t('admin_threads.padam')"
                        :cancel-label="$t('admin_threads.batal')"
                        :label="$t('admin_threads.padam')"
                        :csrf="csrf"
                        trigger-class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-red-300 hover:text-red-700"
                    />
                </div>
            </li>
        </ul>
    </section>
</template>
