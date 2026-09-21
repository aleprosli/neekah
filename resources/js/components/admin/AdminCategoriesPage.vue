<script setup>
/**
 * Categories decide what the marketplace offers and in what order, so this page
 * is a single unpaged list an admin can drag: order is only meaningful while
 * every category is on screen at once. Editing happens in the panel beside it,
 * because a category carries a name, examples, a picture and a status, which is
 * more than fits in a table row.
 */
import { computed, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiField from '../ui/UiField.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';
import { useUploadForm } from '../../composables/useUploadForm.js';

const props = defineProps({
    categories: { type: Array, required: true },
    locales: { type: Array, required: true },
    stats: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    orderUrl: { type: String, required: true },
    imageHint: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const { uploading, percent, error: uploadError, submit: submitUpload } = useUploadForm();

const rows = ref([...props.categories]);
const dragging = ref(null);
const orderError = ref('');

/** null = the add form; a row = editing that category. */
const editing = ref(Object.keys(props.errors).length ? { ...props.categories[0], id: null } : null);
const panelOpen = ref(Object.keys(props.errors).length > 0);

const form = ref(blankForm());
const removeImage = ref(false);

const action = computed(() => (editing.value?.id ? editing.value.update_url : props.storeUrl));

/** An empty value for every language the site is served in. */
function blankPerLocale() {
    return Object.fromEntries(props.locales.map((locale) => [locale.code, '']));
}

function blankForm() {
    return { name: blankPerLocale(), icon: '', examples: blankPerLocale(), is_active: true };
}

const openAdd = () => {
    editing.value = null;
    form.value = blankForm();
    removeImage.value = false;
    panelOpen.value = true;
};

const openEdit = (row) => {
    editing.value = row;
    form.value = { name: { ...row.names }, icon: row.icon, examples: { ...row.examples_all }, is_active: row.is_active };
    removeImage.value = false;
    panelOpen.value = true;
};

const move = (from, to) => {
    if (to < 0 || to >= rows.value.length || from === to) return;

    const next = [...rows.value];
    next.splice(to, 0, ...next.splice(from, 1));
    rows.value = next;
    saveOrder();
};

const drop = (to) => {
    if (dragging.value !== null) move(dragging.value, to);
    dragging.value = null;
};

/** The whole list is sent at once, so a half-applied order is impossible. */
const saveOrder = async () => {
    orderError.value = '';

    try {
        const response = await fetch(props.orderUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf, Accept: 'application/json' },
            body: JSON.stringify({ items: rows.value.map((row, at) => ({ id: row.id, sort_order: at })) }),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
    } catch (problem) {
        orderError.value = 'Susunan tidak dapat disimpan. Muat semula halaman dan cuba lagi.';
        console.error(problem);
    }
};
</script>

<template>
    <div class="flex flex-col gap-6 break-words">
        <dl class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <div v-for="stat in stats" :key="stat.label" class="min-w-0 rounded-2xl border border-line bg-surface-raised p-5">
                <dd class="font-display text-3xl font-semibold">{{ stat.value }}</dd>
                <dt class="mt-1 text-sm text-ink-muted">{{ stat.label }}</dt>
            </div>
        </dl>

        <p v-if="orderError" class="rounded-2xl bg-brand-50 p-4 text-sm text-brand-800">{{ orderError }}</p>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="flex min-w-0 flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-ink-muted">{{ $t('admin_categories.seret_dan_lepas_untuk_susun') }}</p>
                    <button type="button" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700" @click="openAdd">+ Tambah kategori</button>
                </div>

                <ul class="flex flex-col gap-2">
                    <li
                        v-for="(row, at) in rows"
                        :key="row.id"
                        draggable="true"
                        :class="[
                            'flex items-center gap-3 rounded-2xl border bg-surface-raised p-3 transition',
                            dragging === at ? 'opacity-40' : '',
                            editing?.id === row.id ? 'border-brand-400' : 'border-line',
                        ]"
                        @dragstart="dragging = at"
                        @dragover.prevent
                        @drop.prevent="drop(at)"
                        @dragend="dragging = null"
                    >
                        <span class="cursor-grab text-ink-muted select-none" aria-hidden="true">⠿</span>

                        <span class="w-5 shrink-0 text-center text-xs text-ink-muted">{{ at + 1 }}</span>

                        <img v-if="row.illustration" :src="row.illustration" alt="" class="size-10 shrink-0 rounded-xl object-contain" :class="row.has_upload ? '' : 'mix-blend-multiply'">
                        <span v-else class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-xl">{{ row.icon }}</span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate font-medium">{{ row.name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ row.examples || 'Tiada contoh' }}</p>
                        </div>

                        <span class="shrink-0 rounded-full bg-surface-muted px-2.5 py-1 text-xs font-semibold text-ink-muted">{{ row.vendors_count }} vendor</span>

                        <span :class="['hidden shrink-0 rounded-full px-2.5 py-1 text-xs font-semibold sm:inline-flex', row.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-surface-muted text-ink-muted']">
                            {{ row.is_active ? 'Aktif' : 'Tidak aktif' }}
                        </span>

                        <div class="flex shrink-0 items-center gap-1">
                            <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === 0" :aria-label="$t('admin_categories.alih_ke_atas')" @click="move(at, at - 1)">↑</button>
                            <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === rows.length - 1" :aria-label="$t('admin_categories.alih_ke_bawah')" @click="move(at, at + 1)">↓</button>
                            <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="openEdit(row)">{{ $t('admin_categories.edit') }}</button>
                            <UiConfirm
                                v-if="row.vendors_count === 0"
                                :action="row.destroy_url"
                                method="DELETE"
                                tone="danger"
                                :title="$t('admin_categories.padam_kategori_nama', { name: row.name })"
                                :message="$t('admin_categories.kategori_ini_tiada_vendor_jadi')"
                                :confirm-label="$t('admin_categories.padam_kategori')"
                                trigger-class="rounded-full px-2 py-1.5 text-xs font-medium text-ink-muted transition hover:text-brand-700"
                                :csrf="csrf"
                            >{{ $t('admin_categories.padam') }}</UiConfirm>
                        </div>
                    </li>
                </ul>
            </div>

            <form
                v-if="panelOpen"
                :action="action"
                method="POST"
                enctype="multipart/form-data"
                class="flex h-fit min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5"
                @submit="submitUpload"
            >
                <input type="hidden" name="_token" :value="csrf">
                <input v-if="editing?.id" type="hidden" name="_method" value="PUT">

                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-semibold">{{ editing?.id ? $t('admin_categories.edit_kategori') : $t('admin_categories.tambah_kategori') }}</h2>
                    <button type="button" class="text-sm text-ink-muted hover:text-ink" :aria-label="$t('admin_categories.tutup')" @click="panelOpen = false">✕</button>
                </div>

                <div v-if="editing?.id" class="flex items-center gap-3">
                    <img v-if="editing.illustration" :src="editing.illustration" alt="" class="size-14 shrink-0 rounded-xl object-contain" :class="editing.has_upload ? '' : 'mix-blend-multiply'">
                    <span v-else class="flex size-14 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-2xl">{{ editing.icon }}</span>
                    <label v-if="editing.has_upload" class="flex items-center gap-2 text-xs text-ink-muted">
                        <input type="hidden" name="remove_image" value="0">
                        <input v-model="removeImage" type="checkbox" name="remove_image" value="1" class="accent-brand-600">{{ $t('admin_categories.buang_gambar_yang_dimuat_naik') }}</label>
                </div>

                <UiField
                    v-for="locale in locales"
                    :key="`name-${locale.code}`"
                    v-model="form.name[locale.code]"
                    :label="`${$t('admin_categories.nama_kategori')} · ${locale.label}`"
                    :name="`name[${locale.code}]`"
                    :placeholder="$t('admin_categories.kereta_pengantin')"
                    :error="errors[`name.${locale.code}`]"
                    :required="locale.code === locales[0].code"
                />
                <UiField v-model="form.icon" :label="$t('admin_categories.ikon_emoji')" name="icon" placeholder="🚗" :error="errors.icon" :help="$t('admin_categories.dipakai_jika_kategori_ini_tiada')" required />
                <UiField
                    v-for="locale in locales"
                    :key="`examples-${locale.code}`"
                    v-model="form.examples[locale.code]"
                    :label="`${$t('admin_categories.contoh')} · ${locale.label}`"
                    :name="`examples[${locale.code}]`"
                    :placeholder="$t('admin_categories.sewa_kereta_deco_kereta')"
                    :error="errors[`examples.${locale.code}`]"
                />

                <label class="flex flex-col gap-1 text-sm">
                    <span class="font-medium">{{ $t('admin_categories.gambar_kategori') }}</span>
                    <input type="file" name="image" accept="image/*" class="rounded-xl border border-line bg-surface px-3 py-2 text-sm file:mr-3 file:rounded-full file:border-0 file:bg-surface-muted file:px-3 file:py-1.5 file:text-xs">
                    <span class="text-xs text-ink-muted">{{ imageHint }}</span>
                    <span v-if="errors.image" class="text-xs text-red-600">{{ errors.image }}</span>
                </label>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="accent-brand-600">{{ $t('admin_categories.aktif') }}</label>

                <UiUploadProgress :uploading="uploading" :percent="percent" />
                <p v-if="uploadError" class="text-sm text-red-600">{{ uploadError }}</p>

                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700" :disabled="uploading">
                    {{ editing?.id ? $t('common.simpan') : $t('admin_categories.tambah_kategori') }}
                </button>
            </form>
        </div>
    </div>
</template>
