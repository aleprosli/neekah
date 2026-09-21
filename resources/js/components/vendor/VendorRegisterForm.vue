<script setup>
/**
 * Vendor signup: the business, then the owner's account. With `account` it is
 * a couple's existing account switching to vendor, so only the phone is asked.
 */
import { computed, ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiFlagSelect from '../ui/UiFlagSelect.vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTurnstile from '../ui/UiTurnstile.vue';

const props = defineProps({
    action: { type: String, required: true },
    loginUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    categories: { type: Array, required: true },
    states: { type: Array, required: true },
    old: { type: Object, default: () => ({}) },
    errors: { type: Object, default: () => ({}) },
    turnstileSiteKey: { type: String, default: null },
    convertUrl: { type: String, default: null },
    account: { type: Object, default: null },
});

const form = ref({
    business_name: '',
    category_id: '',
    state: '',
    city: '',
    tagline: '',
    name: '',
    phone: '',
    email: '',
    ...props.old,
});

const messages = computed(() => Object.entries(props.errors).filter(([key]) => key !== 'existing_customer').map(([, message]) => message));
const categoryOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: `${c.icon} ${c.name}` })));
</script>

<template>
    <form :action="action" method="POST" class="mt-10 flex flex-col gap-8">
        <input type="hidden" name="_token" :value="csrf">

        <div v-if="errors.existing_customer" class="flex flex-col gap-3 rounded-2xl border border-brand-200 bg-brand-50 p-4 text-sm text-brand-800 sm:flex-row sm:items-center sm:justify-between">
            <p>{{ errors.existing_customer }}</p>
            <a :href="convertUrl" class="shrink-0 rounded-full bg-brand-600 px-4 py-2 text-center font-semibold text-white transition hover:bg-brand-700">Log masuk &amp; tukar akaun</a>
        </div>

        <ul v-if="messages.length" class="flex flex-col gap-1 rounded-2xl bg-brand-50 p-4 text-sm text-brand-800">
            <li v-for="message in messages" :key="message">{{ message }}</li>
        </ul>

        <section class="flex flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-6 sm:p-8">
            <h2 class="font-display text-xl font-semibold">Perniagaan anda</h2>

            <UiField v-model="form.business_name" label="Nama perniagaan" name="business_name" placeholder="ABC Wedding Photography" :error="errors.business_name" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <UiSelect v-model="form.category_id" label="Kategori" name="category_id" :options="categoryOptions" placeholder="Pilih kategori" :error="errors.category_id" required />
                <UiFlagSelect v-model="form.state" label="Negeri" name="state" :options="states" placeholder="Pilih negeri" :error="errors.state" required />
            </div>

            <UiField v-model="form.city" label="Bandar" name="city" placeholder="Alor Setar" :error="errors.city" required />
            <UiField v-model="form.tagline" label="Tagline" name="tagline" placeholder="Candid, natural light wedding photography." :error="errors.tagline" help="Satu ayat pendek yang dipaparkan pada kad vendor." />
        </section>

        <section v-if="account" class="flex flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-6 sm:p-8">
            <h2 class="font-display text-xl font-semibold">Akaun pemilik</h2>
            <p class="min-w-0 break-words text-sm text-ink-muted">{{ account.name }} · {{ account.email }}</p>

            <UiField v-model="form.phone" label="Nombor telefon" name="phone" type="tel" autocomplete="tel" placeholder="012-345 6789" :error="errors.phone" help="Nombor ini dipaparkan sebagai WhatsApp perniagaan anda." required />
        </section>

        <section v-else class="flex flex-col gap-4 rounded-3xl border border-line bg-surface-raised p-6 sm:p-8">
            <h2 class="font-display text-xl font-semibold">Akaun pemilik</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="form.name" label="Nama penuh" name="name" autocomplete="name" :error="errors.name" required />
                <UiField v-model="form.phone" label="Nombor telefon" name="phone" type="tel" autocomplete="tel" placeholder="012-345 6789" :error="errors.phone" required />
            </div>

            <UiField v-model="form.email" label="Emel" name="email" type="email" autocomplete="email" :error="errors.email" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField label="Kata laluan" name="password" type="password" autocomplete="new-password" :error="errors.password" required />
                <UiField label="Sahkan kata laluan" name="password_confirmation" type="password" autocomplete="new-password" required />
            </div>
        </section>

        <div class="flex flex-col items-center gap-3">
            <UiTurnstile v-if="turnstileSiteKey" :site-key="turnstileSiteKey" />

            <button type="submit" class="w-full rounded-full bg-brand-600 py-3.5 text-sm font-semibold text-white transition hover:bg-brand-700 sm:w-auto sm:px-10">{{ account ? 'Tukar ke akaun vendor' : 'Daftar sebagai vendor' }}</button>
            <p v-if="!account" class="text-sm text-ink-muted">Sudah ada akaun? <a :href="loginUrl" class="font-medium text-brand-600 underline underline-offset-4">Log masuk</a></p>
        </div>
    </form>
</template>
