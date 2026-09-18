<script setup>
/**
 * Every authentication form: sign in, register, both password steps and the
 * phone number a Google signup still owes us.
 *
 * They differ only in their fields and their footer, so the controller
 * describes those and this draws them. One form means one place to fix a
 * spacing bug, an autocomplete hint or the captcha.
 */
import { ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiTurnstile from '../ui/UiTurnstile.vue';

const props = defineProps({
    action: { type: String, required: true },
    csrf: { type: String, required: true },
    fields: { type: Array, required: true },
    submitLabel: { type: String, required: true },
    hidden: { type: Object, default: () => ({}) },
    /** [{ label, url, prefix }] shown under the form. */
    links: { type: Array, default: () => [] },
    remember: { type: Boolean, default: false },
    forgotUrl: { type: String, default: null },
    googleUrl: { type: String, default: null },
    turnstileSiteKey: { type: String, default: null },
    notice: { type: String, default: null },
    invitation: { type: Object, default: null },
    /** { title, body } — a short explanation shown above the fields. */
    tip: { type: Object, default: null },
    errors: { type: Object, default: () => ({}) },
});

const values = ref(Object.fromEntries(props.fields.map((field) => [field.name, field.value ?? ''])));
</script>

<template>
    <div v-if="invitation" class="mb-6 flex items-center gap-4 rounded-2xl border border-brand-200 bg-brand-50/60 p-4">
        <span class="flex size-11 shrink-0 items-center justify-center rounded-full border-2 border-brand-500 bg-surface font-display font-semibold text-brand-700">{{ invitation.initial }}</span>
        <div class="min-w-0 text-sm">
            <p class="font-semibold">{{ invitation.inviter }} menjemput anda</p>
            <p class="truncate text-ink-muted">{{ invitation.wedding }}</p>
        </div>
    </div>

    <div v-if="tip" class="mb-6 flex gap-3 rounded-2xl border border-gold-300/70 bg-brand-50/50 p-4">
        <span class="flex size-9 shrink-0 items-center justify-center rounded-full bg-surface-raised text-brand-600 ring-1 ring-gold-300" aria-hidden="true">
            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="14" r="5.5"/><circle cx="16" cy="14" r="5.5"/><path d="m9 5 1.5 2.5h-3L9 5Z"/></svg>
        </span>
        <div class="min-w-0 text-sm">
            <p class="font-semibold">{{ tip.title }}</p>
            <p class="mt-1 text-ink-muted">{{ tip.body }}</p>
        </div>
    </div>

    <p v-if="notice" class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ notice }}</p>

    <form :action="action" method="POST" class="flex flex-col gap-4">
        <input type="hidden" name="_token" :value="csrf">
        <input v-for="(value, name) in hidden" :key="name" type="hidden" :name="name" :value="value">

        <UiField
            v-for="field in fields"
            :key="field.name"
            v-model="values[field.name]"
            :label="field.label"
            :name="field.name"
            :type="field.type || 'text'"
            :placeholder="field.placeholder"
            :autocomplete="field.autocomplete"
            :help="field.help"
            :error="errors[field.name]"
            :required="field.required"
        />

        <div v-if="remember || forgotUrl" class="flex items-center justify-between gap-3 text-sm">
            <label v-if="remember" class="flex items-center gap-2">
                <input type="checkbox" name="remember" class="accent-brand-600">
                Ingat saya
            </label>
            <a v-if="forgotUrl" :href="forgotUrl" class="ml-auto font-medium text-brand-600 underline underline-offset-4">Lupa kata laluan?</a>
        </div>

        <UiTurnstile v-if="turnstileSiteKey" :site-key="turnstileSiteKey" />

        <button type="submit" class="rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ submitLabel }}</button>
    </form>

    <div v-if="googleUrl" class="mt-6 flex flex-col gap-4">
        <div class="flex items-center gap-3 text-xs text-ink-muted">
            <span class="h-px flex-1 bg-line"></span>
            atau
            <span class="h-px flex-1 bg-line"></span>
        </div>

        <a :href="googleUrl" class="flex items-center justify-center gap-3 rounded-full border border-line py-3 text-sm font-semibold transition hover:bg-surface-muted">
            <svg class="size-5" viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#4285F4" d="M23.5 12.3c0-.8-.1-1.6-.2-2.3H12v4.5h6.5a5.6 5.6 0 0 1-2.4 3.6v3h3.9c2.3-2.1 3.5-5.2 3.5-8.8Z" />
                <path fill="#34A853" d="M12 24c3.2 0 5.9-1.1 7.9-2.9l-3.9-3c-1.1.7-2.4 1.2-4 1.2-3.1 0-5.7-2.1-6.6-4.9H1.4v3.1A12 12 0 0 0 12 24Z" />
                <path fill="#FBBC05" d="M5.4 14.4a7.2 7.2 0 0 1 0-4.6V6.7H1.4a12 12 0 0 0 0 10.8l4-3.1Z" />
                <path fill="#EA4335" d="M12 4.8c1.8 0 3.3.6 4.6 1.8l3.4-3.4A12 12 0 0 0 1.4 6.7l4 3.1C6.3 6.9 8.9 4.8 12 4.8Z" />
            </svg>
            Teruskan dengan Google
        </a>
    </div>

    <p v-for="link in links" :key="link.url" class="mt-6 text-center text-sm text-ink-muted">
        {{ link.prefix }} <a :href="link.url" class="font-medium text-brand-600 underline underline-offset-4">{{ link.label }}</a>
    </p>
</template>
