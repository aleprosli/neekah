<script setup>
/**
 * Every admin-editable setting, one section per group, each posting on its own
 * so saving the phone number cannot disturb the SEO wording.
 *
 * The sections are described as data by the controller; this renders them.
 */
import { ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    sections: { type: Array, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

/** One editable copy per section, so typing never writes back into the props. */
const values = ref(
    Object.fromEntries(
        props.sections.map((section) => [
            section.id,
            Object.fromEntries(section.fields.map((field) => [field.name, field.value ?? ''])),
        ]),
    ),
);
</script>

<template>
    <div class="flex max-w-3xl min-w-0 flex-col gap-6">
        <!-- A scrollable row of jump links on a phone, a plain row on a laptop. -->
        <nav class="no-scrollbar -mx-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="Bahagian tetapan">
            <a
                v-for="section in sections"
                :key="`link-${section.id}`"
                :href="`#${section.id}`"
                class="flex shrink-0 items-center gap-2 rounded-full border border-line px-4 py-2 text-sm font-medium whitespace-nowrap transition hover:border-brand-400 hover:text-brand-700"
            >
                <span aria-hidden="true">{{ section.icon }}</span>{{ section.label }}
            </a>
        </nav>

        <section
            v-for="section in sections"
            :id="section.id"
            :key="section.id"
            class="scroll-mt-28 rounded-2xl border border-line bg-surface-raised p-5 sm:p-6"
        >
            <div class="flex flex-wrap items-start justify-between gap-2">
                <h2 class="font-semibold">{{ section.title }}</h2>
                <span
                    v-if="section.badge"
                    :class="['rounded-full px-3 py-1 text-xs font-medium', section.badge.active ? 'bg-emerald-100 text-emerald-800' : 'bg-surface-muted text-ink-muted']"
                >{{ section.badge.label }}</span>
            </div>

            <p class="mt-1 text-sm text-ink-muted">{{ section.description }}</p>

            <form :action="section.action" method="POST" class="mt-5 flex flex-col gap-4">
                <input type="hidden" name="_token" :value="csrf">
                <input type="hidden" name="_method" value="PUT">

                <div :class="section.columns ? 'grid gap-4 sm:grid-cols-2' : 'flex flex-col gap-4'">
                    <template v-for="field in section.fields" :key="field.name">
                        <label v-if="field.type === 'checkbox'" class="flex items-start gap-3 sm:col-span-2">
                            <input type="hidden" :name="field.name" value="0">
                            <input v-model="values[section.id][field.name]" type="checkbox" :name="field.name" value="1" class="mt-1 accent-brand-600">
                            <span>
                                <span class="text-sm font-medium">{{ field.label }}</span>
                                <span v-if="field.help" class="block text-xs text-ink-muted">{{ field.help }}</span>
                            </span>
                        </label>

                        <UiTextarea
                            v-else-if="field.type === 'textarea'"
                            v-model="values[section.id][field.name]"
                            :class="field.wide ? 'sm:col-span-2' : ''"
                            :label="field.label"
                            :name="field.name"
                            :rows="field.rows || 3"
                            :placeholder="field.placeholder"
                            :help="field.help"
                            :maxlength="field.maxlength"
                            :error="errors[field.name]"
                            :required="field.required"
                        />

                        <UiSelect
                            v-else-if="field.type === 'select'"
                            v-model="values[section.id][field.name]"
                            :label="field.label"
                            :name="field.name"
                            :options="field.options"
                            :help="field.help"
                            :error="errors[field.name]"
                            :required="field.required"
                        />

                        <UiField
                            v-else
                            v-model="values[section.id][field.name]"
                            :class="field.wide ? 'sm:col-span-2' : ''"
                            :label="field.label"
                            :name="field.name"
                            :type="field.type || 'text'"
                            :placeholder="field.placeholder"
                            :help="field.help"
                            :min="field.min"
                            :max="field.max"
                            :step="field.step"
                            :maxlength="field.maxlength"
                            autocomplete="off"
                            :error="errors[field.name]"
                            :required="field.required"
                        />
                    </template>
                </div>

                <!-- What the tagline and description will look like in Google. -->
                <div v-if="section.preview" class="rounded-xl bg-surface-muted px-4 py-3">
                    <p class="text-xs font-medium text-ink-muted">Pratonton hasil carian</p>
                    <p class="mt-2 truncate text-sm text-brand-700">{{ section.preview.site }} — {{ values[section.id].tagline }}</p>
                    <p class="text-xs break-words text-ink-muted">{{ values[section.id].description }}</p>
                </div>

                <p v-if="section.warning" class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs break-words text-amber-900" v-html="section.warning"></p>
                <p v-if="section.note" class="rounded-xl bg-surface-muted px-4 py-3 text-xs break-words text-ink-muted" v-html="section.note"></p>

                <div>
                    <button type="submit" class="w-full rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto">{{ section.submit }}</button>
                </div>
            </form>
        </section>
    </div>
</template>
