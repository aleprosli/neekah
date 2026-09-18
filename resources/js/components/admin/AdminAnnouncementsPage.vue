<script setup>
/**
 * Write once, reach everybody. The audience cards carry their live recipient
 * count, because "hantar kepada semua" has to say how many people that is
 * before it is pressed, and the send is behind a confirmation for the same
 * reason: an announcement cannot be unsent.
 */
import { computed, ref } from 'vue';
import DataTable from '../ui/DataTable.vue';
import UiBadge from '../ui/UiBadge.vue';
import UiConfirmSubmit from '../ui/UiConfirmSubmit.vue';
import UiField from '../ui/UiField.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    audiences: { type: Array, required: true },
    announcements: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    testUrl: { type: String, required: true },
    notice: { type: String, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const columns = [
    { key: 'subject', label: 'Tajuk' },
    { key: 'audience', label: 'Penerima' },
    { key: 'recipients', label: 'Dihantar kepada' },
    { key: 'sent_at', label: 'Tarikh' },
    { key: 'author', label: 'Oleh' },
    { key: 'status_label', label: 'Status' },
];

const form = ref({
    audience: props.audiences[0]?.value ?? 'everyone',
    subject: '',
    body: '',
    action_label: '',
    action_url: '',
});

const chosen = computed(() => props.audiences.find((audience) => audience.value === form.value.audience));

/**
 * The test send posts the same fields to a different endpoint. requestSubmit()
 * rather than submit(), so the browser still points at anything left blank.
 */
const sendTest = (event) => {
    const element = event.target.form;
    element.action = props.testUrl;
    element.requestSubmit();
};
</script>

<template>
    <div class="flex flex-col gap-6 break-words">
        <p v-if="notice" class="rounded-2xl bg-emerald-50 p-4 text-sm text-emerald-800">{{ notice }}</p>

        <form :action="storeUrl" method="POST" class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-5">
            <input type="hidden" name="_token" :value="csrf">

            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium">Penerima</legend>

                <div class="grid gap-2 sm:grid-cols-3">
                    <label
                        v-for="audience in audiences"
                        :key="audience.value"
                        :class="[
                            'flex min-w-0 cursor-pointer flex-col gap-1 rounded-xl border p-4 transition',
                            form.audience === audience.value ? 'border-brand-400 bg-brand-50' : 'border-line hover:border-brand-400',
                        ]"
                    >
                        <span class="flex items-center gap-2">
                            <input v-model="form.audience" type="radio" name="audience" :value="audience.value" class="accent-brand-600">
                            <span class="text-sm font-medium">{{ audience.label }}</span>
                        </span>
                        <span class="font-display text-2xl font-semibold">{{ audience.count }}</span>
                        <span class="text-xs text-ink-muted">{{ audience.description }}</span>
                    </label>
                </div>

                <span v-if="errors.audience" class="text-xs text-brand-700">{{ errors.audience }}</span>
            </fieldset>

            <UiField v-model="form.subject" label="Tajuk" name="subject" placeholder="Penambahbaikan checklist majlis" :error="errors.subject" required />
            <UiTextarea
                v-model="form.body"
                label="Isi kandungan"
                name="body"
                rows="8"
                placeholder="Tulis mesej anda di sini. Tinggalkan satu baris kosong antara perenggan."
                :error="errors.body"
                help="Setiap perenggan dipaparkan sebagai satu baris dalam emel."
                required
            />

            <div class="grid gap-3 sm:grid-cols-2">
                <UiField v-model="form.action_label" label="Teks butang (pilihan)" name="action_label" placeholder="Buka checklist" :error="errors.action_label" />
                <UiField v-model="form.action_url" label="Pautan butang (pilihan)" name="action_url" type="url" placeholder="https://neekah.my/checklist" :error="errors.action_url" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <UiConfirmSubmit
                    :title="`Hantar kepada ${chosen?.count ?? 0} penerima?`"
                    :message="`${chosen?.label}. Emel dan notifikasi akan dihantar, dan pengumuman yang sudah keluar tidak boleh ditarik balik.`"
                    confirm-label="Hantar sekarang"
                    button-class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700"
                >Hantar pengumuman</UiConfirmSubmit>

                <button type="button" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="sendTest">
                    Hantar ujian kepada saya
                </button>
            </div>
        </form>

        <section class="flex flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">Pengumuman lepas</h2>

            <DataTable
                :rows="announcements"
                :columns="columns"
                empty-title="Belum ada pengumuman"
                empty-message="Pengumuman yang dihantar akan disenaraikan di sini."
                :csrf="csrf"
            >
                <template #cell-subject="{ row }">
                    <a :href="row.url" class="font-medium hover:text-brand-700">{{ row.subject }}</a>
                </template>

                <template #cell-status_label="{ row }">
                    <UiBadge :label="row.status_label" :tone="row.status_tone" />
                </template>
            </DataTable>
        </section>
    </div>
</template>
