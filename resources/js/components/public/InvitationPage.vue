<script setup>
/** A partner invitation, as the person who received the link sees it. */
defineProps({
    invitation: { type: Object, required: true },
    facts: { type: Array, required: true },
    state: { type: String, required: true },
    acceptUrl: { type: String, required: true },
    dashboardUrl: { type: String, required: true },
    browseUrl: { type: String, required: true },
    footnote: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="rounded-3xl border border-line bg-surface-raised p-6 text-center shadow-xl shadow-brand-900/5 sm:p-8">
        <span class="text-4xl">💌</span>
        <h1 class="mt-3 font-display text-2xl font-semibold tracking-tight">{{ invitation.inviter }} menjemput anda</h1>
        <p class="mt-2 text-sm text-ink-muted">{{ $t('invitation.purpose') }}</p>

        <dl class="mt-6 flex flex-col gap-2 rounded-2xl bg-surface-muted p-5 text-left text-sm">
            <div v-for="fact in facts" :key="fact.label" class="flex justify-between gap-3">
                <dt class="text-ink-muted">{{ fact.label }}</dt>
                <dd class="font-semibold">{{ fact.value }}</dd>
            </div>
        </dl>

        <ul v-if="Object.keys(errors).length" class="mt-4 flex flex-col gap-1 rounded-xl bg-brand-50 p-3 text-sm text-brand-800">
            <li v-for="message in Object.values(errors)" :key="message">{{ message }}</li>
        </ul>

        <template v-if="state === 'member'">
            <p class="mt-6 text-sm text-ink-muted">{{ $t('invitation.already_member') }}</p>
            <a :href="dashboardUrl" class="mt-3 inline-flex rounded-full bg-brand-600 px-6 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('invitation.open_wedding') }}</a>
        </template>

        <p v-else-if="state === 'expired'" class="mt-6 rounded-xl bg-surface-muted p-4 text-sm text-ink-muted">
            Jemputan ini sudah tamat tempoh atau telah digunakan. Minta {{ invitation.inviter }} menghantar jemputan baharu.
        </p>

        <template v-else>
            <p class="mt-6 text-sm text-ink-muted">{{ $t('invitation.shared_note') }}</p>

            <form :action="acceptUrl" method="POST" class="mt-4">
                <input type="hidden" name="_token" :value="csrf">
                <button type="submit" class="w-full rounded-full bg-brand-600 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ $t('invitation.accept') }}</button>
            </form>

            <a :href="browseUrl" class="mt-3 inline-block text-sm text-ink-muted underline underline-offset-4">{{ $t('invitation.decline') }}</a>
        </template>
    </div>

    <p class="mt-4 text-center text-xs text-ink-muted">{{ footnote }}</p>
</template>
