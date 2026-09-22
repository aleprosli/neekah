<script setup>
/**
 * Write once, reach everybody — or exactly the handful you mean. The audience
 * cards carry their live recipient count, because "hantar kepada semua" has to
 * say how many people that is before it is pressed, and the send is behind a
 * confirmation for the same reason: an announcement cannot be unsent.
 *
 * "Pilih sendiri" searches accounts on the server rather than shipping the
 * whole user table to the browser, and also takes addresses typed by hand,
 * which may belong to nobody with an account.
 */
import { computed, ref, watch } from 'vue';
import DataTable from '../ui/DataTable.vue';
import UiBadge from '../ui/UiBadge.vue';
import UiConfirmSubmit from '../ui/UiConfirmSubmit.vue';
import UiField from '../ui/UiField.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    audiences: { type: Array, required: true },
    presets: { type: Array, default: () => [] },
    announcements: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    testUrl: { type: String, required: true },
    searchUrl: { type: String, required: true },
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
    emails: '',
    action_label: '',
    action_url: '',
});

const chosen = computed(() => props.audiences.find((audience) => audience.value === form.value.audience));

/*
| Presets
|--------------------------------------------------------------------------
| A starting point, never a send. The fields are filled and the admin reads
| and edits them like anything else they typed — the presets with a [bracket]
| in them are written expecting exactly that.
*/
const applied = ref(null);

const applyPreset = (preset) => {
    form.value = {
        ...form.value,
        audience: preset.audience,
        subject: preset.subject,
        body: preset.body,
        action_label: preset.action_label ?? '',
        action_url: preset.action_url ?? '',
    };

    // A preset carries its own audience, so a list picked by hand for the last
    // one would otherwise be posted with it.
    if (preset.audience !== 'custom') {
        picked.value = [];
        form.value.emails = '';
    }

    applied.value = preset.key;
};

const clearForm = () => {
    form.value = { ...form.value, subject: '', body: '', action_label: '', action_url: '' };
    applied.value = null;
};

/** A preset the admin has since edited is no longer that preset. */
watch(
    () => [form.value.subject, form.value.body],
    () => {
        const preset = props.presets.find((item) => item.key === applied.value);

        if (preset && (preset.subject !== form.value.subject || preset.body !== form.value.body)) {
            applied.value = null;
        }
    },
);

/** Accounts picked by hand, kept in full so the list can show who they are. */
const picked = ref([]);
const search = ref('');
const results = ref([]);
const searching = ref(false);
let pending = null;

const typedAddresses = computed(() =>
    form.value.emails
        .split(/[\n,;]/)
        .map((address) => address.trim())
        .filter(Boolean),
);

const total = computed(() => (chosen.value?.custom ? picked.value.length + typedAddresses.value.length : (chosen.value?.count ?? 0)));

const add = (user) => {
    if (!picked.value.some((row) => row.id === user.id)) {
        picked.value = [...picked.value, user];
    }

    search.value = '';
    results.value = [];
};

const remove = (id) => {
    picked.value = picked.value.filter((row) => row.id !== id);
};

/** One request per pause in typing, not one per keystroke. */
watch(search, (keyword) => {
    clearTimeout(pending);

    if (keyword.trim().length < 2) {
        results.value = [];

        return;
    }

    pending = setTimeout(async () => {
        searching.value = true;

        try {
            // Built through URL, not string concatenation: an endpoint that
            // gains a query of its own would otherwise collide with this one.
            const url = new URL(props.searchUrl, window.location.origin);
            url.searchParams.set('search', keyword.trim());

            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) throw new Error(`HTTP ${response.status}`);

            const body = await response.json();
            results.value = body.data.filter((user) => !picked.value.some((row) => row.id === user.id));
        } catch (problem) {
            results.value = [];
            console.error(problem);
        } finally {
            searching.value = false;
        }
    }, 250);
});

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

            <fieldset v-if="presets.length" class="flex flex-col gap-2">
                <legend class="text-sm font-medium">{{ $t('admin_announcements.mula_dengan_preset') }}</legend>
                <p class="text-xs text-ink-muted">{{ $t('admin_announcements.preset_mengisi_borang_sahaja') }}</p>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                    <button
                        v-for="preset in presets"
                        :key="preset.key"
                        type="button"
                        :class="[
                            'flex min-w-0 flex-col gap-0.5 rounded-xl border p-3 text-left transition',
                            applied === preset.key ? 'border-brand-400 bg-brand-50' : 'border-line hover:border-brand-400',
                        ]"
                        @click="applyPreset(preset)"
                    >
                        <span class="truncate text-sm font-medium">{{ preset.label }}</span>
                        <span class="text-xs break-words text-ink-muted">{{ preset.hint }}</span>
                    </button>
                </div>

                <button v-if="form.subject || form.body" type="button" class="w-fit text-xs font-medium text-ink-muted underline underline-offset-4 hover:text-brand-700" @click="clearForm()">
                    {{ $t('admin_announcements.kosongkan_borang') }}
                </button>
            </fieldset>

            <fieldset class="flex flex-col gap-2">
                <legend class="text-sm font-medium">{{ $t('admin_announcements.penerima') }}</legend>

                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
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
                        <span class="font-display text-2xl font-semibold">{{ audience.custom ? (picked.length + typedAddresses.length) : audience.count }}</span>
                        <span class="text-xs text-ink-muted">{{ audience.description }}</span>
                    </label>
                </div>

                <span v-if="errors.audience" class="text-xs text-brand-700">{{ errors.audience }}</span>
            </fieldset>

            <div v-if="chosen?.custom" class="flex flex-col gap-3 rounded-xl border border-line p-4">
                <label class="flex flex-col gap-1.5">
                    <span class="text-sm font-medium">{{ $t('admin_announcements.cari_pengguna') }}</span>
                    <input
                        v-model="search"
                        type="search"
                        :placeholder="$t('admin_announcements.taip_nama_atau_emel')"
                        class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none"
                    >
                    <span class="text-xs text-ink-muted">{{ $t('admin_announcements.admin_dan_akaun_yang_dinyahaktifkan') }}</span>
                </label>

                <ul v-if="results.length" class="divide-y divide-line rounded-xl border border-line">
                    <li v-for="user in results" :key="user.id" class="flex items-center gap-3 p-3">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ user.name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ user.email }} · {{ user.role }}</p>
                        </div>
                        <button type="button" class="shrink-0 rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="add(user)">{{ $t('admin_announcements.tambah') }}</button>
                    </li>
                </ul>

                <p v-else-if="searching" class="text-xs text-ink-muted">{{ $t('admin_announcements.mencari') }}</p>
                <p v-else-if="search.trim().length >= 2" class="text-xs text-ink-muted">{{ $t('admin_announcements.tiada_pengguna_sepadan') }}</p>

                <div v-if="picked.length" class="flex flex-wrap gap-2">
                    <span v-for="user in picked" :key="user.id" class="inline-flex items-center gap-2 rounded-full bg-surface-muted px-3 py-1.5 text-xs">
                        <input type="hidden" name="user_ids[]" :value="user.id">
                        <span class="font-medium">{{ user.name }}</span>
                        <button type="button" class="text-ink-muted transition hover:text-brand-700" :aria-label="`Buang ${user.name}`" @click="remove(user.id)">✕</button>
                    </span>
                </div>

                <span v-if="errors.user_ids" class="text-xs text-brand-700">{{ errors.user_ids }}</span>

                <UiTextarea
                    v-model="form.emails"
                    :label="$t('admin_announcements.atau_taip_alamat_emel')"
                    name="emails"
                    rows="3"
                    placeholder="aina@example.com, hakim@example.com"
                    :error="errors.emails"
                    :help="$t('admin_announcements.satu_alamat_setiap_baris_atau')"
                />
            </div>

            <UiField v-model="form.subject" :label="$t('admin_announcements.tajuk')" name="subject" :placeholder="$t('admin_announcements.penambahbaikan_checklist_majlis')" :error="errors.subject" required />
            <UiTextarea
                v-model="form.body"
                :label="$t('admin_announcements.isi_kandungan')"
                name="body"
                rows="8"
                :placeholder="$t('admin_announcements.tulis_mesej_anda_di_sini')"
                :error="errors.body"
                :help="$t('admin_announcements.setiap_perenggan_dipaparkan_sebagai_satu')"
                required
            />

            <div class="grid gap-3 sm:grid-cols-2">
                <UiField v-model="form.action_label" :label="$t('admin_announcements.teks_butang_pilihan')" name="action_label" :placeholder="$t('admin_announcements.buka_checklist')" :error="errors.action_label" />
                <UiField v-model="form.action_url" :label="$t('admin_announcements.pautan_butang_pilihan')" name="action_url" type="url" placeholder="https://neekah.my/checklist" :error="errors.action_url" />
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <UiConfirmSubmit
                    :title="$t('admin_announcements.hantar_kepada_penerima', { count: total })"
                    :message="$t('admin_announcements.emel_dan_notifikasi_akan_dihantar', { audience: chosen?.label })"
                    :confirm-label="$t('admin_announcements.hantar_sekarang')"
                    button-class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700"
                >{{ $t('admin_announcements.hantar_pengumuman') }}</UiConfirmSubmit>

                <button type="button" class="rounded-full border border-line px-5 py-2.5 text-sm font-medium transition hover:border-brand-400" @click="sendTest">{{ $t('admin_announcements.hantar_ujian_kepada_saya') }}</button>
            </div>
        </form>

        <section class="flex flex-col gap-3">
            <h2 class="font-display text-xl font-semibold">{{ $t('admin_announcements.pengumuman_lepas') }}</h2>

            <DataTable
                :rows="announcements"
                :columns="columns"
                :empty-title="$t('admin_announcements.belum_ada_pengumuman')"
                :empty-message="$t('admin_announcements.pengumuman_yang_dihantar_akan_disenaraikan')"
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
