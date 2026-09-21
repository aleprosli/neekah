<script setup>
/** The wedding project itself: date, place and budget everything else reads. */
import { ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiFlagSelect from '../ui/UiFlagSelect.vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    editing: { type: Boolean, default: false },
    wedding: { type: Object, required: true },
    states: { type: Array, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const form = ref({ ...props.wedding });
</script>

<template>
    <form :action="action" method="POST" class="flex max-w-2xl flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="editing" type="hidden" name="_method" value="PUT">

        <UiField v-model="form.title" label="Nama majlis" name="title" placeholder="Aina & Hakim" :error="errors.title" required />

        <div class="grid gap-4 sm:grid-cols-2">
            <UiField v-model="form.event_date" label="Tarikh majlis" name="event_date" type="date" :error="errors.event_date" required />
            <UiField v-model="form.budget" label="Bajet (RM)" name="budget" type="number" step="100" min="0" :error="errors.budget" required />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <UiField v-model="form.city" label="Bandar" name="city" placeholder="Alor Setar" :error="errors.city" required />
            <UiFlagSelect v-model="form.state" label="Negeri" name="state" :options="states" placeholder="Pilih negeri" :error="errors.state" required />
        </div>

        <UiTextarea v-model="form.notes" label="Nota (pilihan)" name="notes" :rows="3" placeholder="Tema, jumlah tetamu, permintaan khas" :error="errors.notes" />

        <div class="flex flex-wrap gap-2 pt-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ editing ? 'Simpan' : 'Cipta majlis' }}</button>
            <a :href="cancelUrl" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
        </div>
    </form>
</template>
