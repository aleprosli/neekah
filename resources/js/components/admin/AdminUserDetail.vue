<script setup>
/**
 * One account: what it has done, and the fixes an admin may apply to it. Each
 * action says why it is unavailable rather than quietly disappearing, so an
 * admin knows the difference between "not allowed" and "not built".
 */
import { ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import VendorRegisterForm from '../vendor/VendorRegisterForm.vue';

const props = defineProps({
    user: { type: Object, required: true },
    facts: { type: Array, required: true },
    actions: { type: Array, required: true },
    vendorForm: { type: Object, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const card = 'flex min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5';

/** The switch-to-vendor form opens straight away when it came back with errors. */
const showVendorForm = ref(Object.keys(props.errors).length > 0);

const triggerClass = (tone) =>
    tone === 'danger'
        ? 'self-start rounded-full border border-red-200 px-4 py-2 text-sm font-medium text-red-700 transition hover:border-red-400'
        : 'self-start rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700';
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-[minmax(0,1fr)_360px]">
        <dl class="grid min-w-0 content-start gap-3 rounded-2xl border border-line p-5 text-sm sm:grid-cols-2 [&>div]:min-w-0">
            <div v-for="fact in facts" :key="fact.label">
                <dt class="text-ink-muted">{{ fact.label }}</dt>
                <dd class="font-semibold break-words">{{ fact.value }}</dd>
            </div>
        </dl>

        <aside class="flex min-w-0 flex-col gap-4">
            <p v-if="user.is_admin" class="rounded-2xl border border-dashed border-line p-4 text-sm text-ink-muted">
                Akaun admin tidak diurus dari sini.
            </p>

            <template v-else>
                <div v-for="action in actions" :key="action.key" :class="card">
                    <h2 class="text-sm font-semibold">{{ action.heading }}</h2>
                    <p class="text-sm text-ink-muted">{{ action.body }}</p>

                    <!-- The switch-to-vendor case is a whole form, not a button. -->
                    <template v-if="action.key === 'switchToVendor' && action.allowed && vendorForm">
                        <button v-if="!showVendorForm" type="button" :class="triggerClass()" @click="showVendorForm = true">
                            Isi maklumat perniagaan
                        </button>
                        <VendorRegisterForm v-else v-bind="vendorForm" />
                    </template>

                    <UiConfirm
                        v-else-if="action.allowed"
                        :action="action.url"
                        :method="action.method"
                        :tone="action.tone || 'brand'"
                        :title="action.confirm_title"
                        :message="action.confirm_message"
                        :confirm-label="action.confirm_label"
                        :trigger-class="triggerClass(action.tone)"
                        :csrf="csrf"
                    >{{ action.label }}</UiConfirm>
                </div>
            </template>
        </aside>
    </div>
</template>
