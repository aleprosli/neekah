<script setup>
/**
 * The master checklist every couple starts from: eight phases of a Malaysian
 * wedding, each a list an admin drags into the order the couple walks it. The
 * whole list is on one page and unpaged, because order only means something
 * while you can see what comes before and after.
 */
import { computed, ref } from 'vue';
import UiConfirm from '../ui/UiConfirm.vue';
import UiField from '../ui/UiField.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    locales: { type: Array, required: true },
    sections: { type: Array, required: true },
    categories: { type: Array, required: true },
    stats: { type: Array, required: true },
    sectionStoreUrl: { type: String, required: true },
    itemStoreUrl: { type: String, required: true },
    orderUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const list = ref(props.sections.map((section) => ({ ...section, items: [...section.items] })));
const open = ref(new Set(list.value.slice(0, 1).map((section) => section.id)));
const draggingSection = ref(null);
const draggingItem = ref(null);
const orderError = ref('');

/** What the side panel is editing: a section, an item, or nothing. */
const panel = ref(null);
const form = ref({});

const action = computed(() => {
    if (!panel.value) return '';
    if (panel.value.kind === 'section') return panel.value.row?.update_url ?? props.sectionStoreUrl;
    return panel.value.row?.update_url ?? props.itemStoreUrl;
});

const toggle = (id) => {
    const next = new Set(open.value);
    next.has(id) ? next.delete(id) : next.add(id);
    open.value = next;
};

/** A value per language, from what the server sent or empty. */
const perLocale = (values) => Object.fromEntries(props.locales.map((l) => [l.code, values?.[l.code] ?? '']));

const editSection = (row = null) => {
    panel.value = { kind: 'section', row };
    form.value = { title: perLocale(row?.titles), icon: row?.icon ?? '', note: perLocale(row?.notes_all), is_active: row ? row.is_active : true };
};

const editItem = (sectionId, row = null) => {
    panel.value = { kind: 'item', row, sectionId };
    form.value = {
        checklist_section_id: row?.checklist_section_id ?? sectionId,
        title: perLocale(row?.titles),
        group: perLocale(row?.groups),
        category_id: row?.category_id ?? '',
        months_before: row?.months_before ?? '',
        notes: perLocale(row?.notes_all),
        is_active: row ? row.is_active : true,
    };
};

const moveSection = (from, to) => {
    if (to < 0 || to >= list.value.length || from === to) return;

    const next = [...list.value];
    next.splice(to, 0, ...next.splice(from, 1));
    list.value = next;
    saveOrder();
};

const moveItem = (section, from, to) => {
    if (to < 0 || to >= section.items.length || from === to) return;

    section.items.splice(to, 0, ...section.items.splice(from, 1));
    saveOrder();
};

const dropItem = (section, to) => {
    if (draggingItem.value && draggingItem.value.sectionId === section.id) {
        moveItem(section, draggingItem.value.at, to);
    }

    draggingItem.value = null;
};

/** Sections and every item go up together, so a drag can never half-apply. */
const saveOrder = async () => {
    orderError.value = '';

    const payload = {
        sections: list.value.map((section, at) => ({ id: section.id, sort_order: at })),
        items: list.value.flatMap((section) =>
            section.items.map((item, at) => ({ id: item.id, checklist_section_id: section.id, sort_order: at })),
        ),
    };

    try {
        const response = await fetch(props.orderUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': props.csrf, Accept: 'application/json' },
            body: JSON.stringify(payload),
        });

        if (!response.ok) throw new Error(`HTTP ${response.status}`);
    } catch (problem) {
        orderError.value = 'Susunan tidak dapat disimpan. Muat semula halaman dan cuba lagi.';
        console.error(problem);
    }
};

const dueLabel = (months) => {
    if (months === null || months === undefined) return 'Tiada tarikh akhir';
    if (months === 0) return 'Hari majlis';

    return `${months} bulan sebelum`;
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

        <p class="rounded-2xl border border-line bg-surface-raised p-4 text-sm text-ink-muted">{{ $t('admin_checklist.tugasan_yang_ditambah_di_sini') }}</p>

        <p v-if="orderError" class="rounded-2xl bg-brand-50 p-4 text-sm text-brand-800">{{ orderError }}</p>

        <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_340px]">
            <div class="flex min-w-0 flex-col gap-3">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm text-ink-muted">{{ $t('admin_checklist.seret_untuk_susun_fasa_dan') }}</p>
                    <button type="button" class="rounded-full bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700" @click="editSection()">+ Tambah fasa</button>
                </div>

                <ul class="flex flex-col gap-3">
                    <li
                        v-for="(section, at) in list"
                        :key="section.id"
                        draggable="true"
                        :class="[
                            'min-w-0 rounded-2xl border bg-surface-raised transition',
                            draggingSection === at ? 'opacity-40' : '',
                            panel?.kind === 'section' && panel.row?.id === section.id ? 'border-brand-400' : 'border-line',
                        ]"
                        @dragstart="draggingSection = at"
                        @dragover.prevent
                        @drop.prevent="draggingSection !== null && moveSection(draggingSection, at)"
                        @dragend="draggingSection = null"
                    >
                        <div class="flex items-center gap-3 p-4">
                            <span class="cursor-grab text-ink-muted select-none" aria-hidden="true">⠿</span>
                            <span class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-surface-muted text-xl">{{ section.icon || '☑' }}</span>

                            <button type="button" class="min-w-0 flex-1 text-left" @click="toggle(section.id)">
                                <p class="truncate font-medium">{{ at + 1 }}. {{ section.title }}</p>
                                <p class="truncate text-xs text-ink-muted">{{ section.items.length }} tugasan</p>
                            </button>

                            <span v-if="!section.is_active" class="hidden shrink-0 rounded-full bg-surface-muted px-2.5 py-1 text-xs font-semibold text-ink-muted sm:inline-flex">{{ $t('admin_checklist.tidak_aktif') }}</span>

                            <div class="flex shrink-0 items-center gap-1">
                                <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === 0" :aria-label="$t('admin_checklist.alih_ke_atas')" @click="moveSection(at, at - 1)">↑</button>
                                <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="at === list.length - 1" :aria-label="$t('admin_checklist.alih_ke_bawah')" @click="moveSection(at, at + 1)">↓</button>
                                <button type="button" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="editSection(section)">{{ $t('admin_checklist.edit') }}</button>
                                <UiConfirm
                                    :action="section.destroy_url"
                                    method="DELETE"
                                    tone="danger"
                                    :title="$t('admin_checklist.padam_fasa_nama', { name: section.title })"
                                    :message="$t('admin_checklist.tugasan_akan_hilang', { count: section.items.length })"
                                    :confirm-label="$t('admin_checklist.padam_fasa')"
                                    trigger-class="rounded-full px-2 py-1.5 text-xs font-medium text-ink-muted transition hover:text-brand-700"
                                    :csrf="csrf"
                                >{{ $t('admin_checklist.padam_2') }}</UiConfirm>
                            </div>
                        </div>

                        <div v-if="open.has(section.id)" class="border-t border-line p-4 pt-3">
                            <p v-if="section.note" class="mb-3 rounded-xl bg-surface-muted p-3 text-xs text-ink-muted">{{ section.note }}</p>

                            <ul class="flex flex-col gap-1.5">
                                <li
                                    v-for="(item, index) in section.items"
                                    :key="item.id"
                                    draggable="true"
                                    class="flex items-center gap-3 rounded-xl border border-line px-3 py-2"
                                    @dragstart.stop="draggingItem = { sectionId: section.id, at: index }"
                                    @dragover.prevent
                                    @drop.prevent.stop="dropItem(section, index)"
                                    @dragend="draggingItem = null"
                                >
                                    <span class="cursor-grab text-xs text-ink-muted select-none" aria-hidden="true">⠿</span>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm" :class="item.is_active ? '' : 'text-ink-muted line-through'">{{ item.title }}</p>
                                        <p class="flex flex-wrap items-center gap-x-2 text-xs text-ink-muted">
                                            <span v-if="item.group">{{ item.group }}</span>
                                            <span>{{ dueLabel(item.months_before) }}</span>
                                            <span v-if="item.category">{{ item.category }}</span>
                                        </p>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1">
                                        <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="index === 0" :aria-label="$t('admin_checklist.alih_ke_atas_2')" @click="moveItem(section, index, index - 1)">↑</button>
                                        <button type="button" class="rounded-full px-2 py-1 text-xs text-ink-muted transition hover:bg-surface-muted disabled:opacity-30" :disabled="index === section.items.length - 1" :aria-label="$t('admin_checklist.alih_ke_bawah_2')" @click="moveItem(section, index, index + 1)">↓</button>
                                        <button type="button" class="rounded-full px-2 py-1.5 text-xs font-medium transition hover:text-brand-700" @click="editItem(section.id, item)">{{ $t('admin_checklist.edit_2') }}</button>
                                        <UiConfirm
                                            :action="item.destroy_url"
                                            method="DELETE"
                                            tone="danger"
                                            :title="$t('admin_checklist.padam_tugasan_ini_dari_senarai')"
                                            :message="item.title"
                                            :confirm-label="$t('admin_checklist.padam')"
                                            trigger-class="rounded-full px-2 py-1.5 text-xs font-medium text-ink-muted transition hover:text-brand-700"
                                            :csrf="csrf"
                                        >{{ $t('admin_checklist.padam_3') }}</UiConfirm>
                                    </div>
                                </li>
                            </ul>

                            <button type="button" class="mt-3 rounded-full border border-line px-4 py-1.5 text-xs font-medium transition hover:border-brand-400" @click="editItem(section.id)">+ Tambah tugasan</button>
                        </div>
                    </li>
                </ul>
            </div>

            <form v-if="panel" :action="action" method="POST" class="flex h-fit min-w-0 flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <input type="hidden" name="_token" :value="csrf">
                <input v-if="panel.row" type="hidden" name="_method" value="PUT">

                <div class="flex items-start justify-between gap-2">
                    <h2 class="font-semibold">
                        {{ panel.kind === 'section' ? (panel.row ? $t('admin_checklist.edit_fasa') : $t('admin_checklist.tambah_fasa')) : (panel.row ? $t('admin_checklist.edit_tugasan') : $t('admin_checklist.tambah_tugasan')) }}
                    </h2>
                    <button type="button" class="text-sm text-ink-muted hover:text-ink" :aria-label="$t('admin_checklist.tutup')" @click="panel = null">✕</button>
                </div>

                <template v-if="panel.kind === 'section'">
                    <UiField v-for="locale in locales" :key="`st-${locale.code}`" v-model="form.title[locale.code]" :label="`${$t('admin_checklist.nama_fasa')} · ${locale.label}`" :name="`title[${locale.code}]`" :placeholder="$t('admin_checklist.urusan_borang_dokumen_nikah')" :error="errors[`title.${locale.code}`]" :required="locale.code === locales[0].code" />
                    <UiField v-model="form.icon" :label="$t('admin_checklist.ikon_emoji')" name="icon" placeholder="📄" :error="errors.icon" />
                    <UiTextarea v-for="locale in locales" :key="`sn-${locale.code}`" v-model="form.note[locale.code]" :label="`${$t('admin_checklist.nota')} · ${locale.label}`" :name="`note[${locale.code}]`" rows="3" :error="errors[`note.${locale.code}`]" :help="$t('admin_checklist.dipaparkan_di_atas_fasa_ini')" />
                </template>

                <template v-else>
                    <input type="hidden" name="checklist_section_id" :value="form.checklist_section_id">

                    <UiField v-for="locale in locales" :key="`it-${locale.code}`" v-model="form.title[locale.code]" :label="`${$t('admin_checklist.tugasan')} · ${locale.label}`" :name="`title[${locale.code}]`" :placeholder="$t('admin_checklist.submit_permohonan_ke_pejabat_agama')" :error="errors[`title.${locale.code}`]" :required="locale.code === locales[0].code" />
                    <UiField v-for="locale in locales" :key="`ig-${locale.code}`" v-model="form.group[locale.code]" :label="`${$t('admin_checklist.kumpulan')} · ${locale.label}`" :name="`group[${locale.code}]`" :placeholder="$t('admin_checklist.dokumen_asas')" :error="errors[`group.${locale.code}`]" :help="$t('admin_checklist.tajuk_kecil_dalam_fasa_ini')" />

                    <label class="flex flex-col gap-1.5">
                        <span class="text-sm font-medium">{{ $t('admin_checklist.kategori_vendor') }}</span>
                        <select v-model="form.category_id" name="category_id" class="nk-select rounded-xl border border-line bg-surface px-3 py-2.5 pr-9 text-sm focus:border-brand-400 focus:outline-none">
                            <option value="">{{ $t('admin_checklist.tiada') }}</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.icon }} {{ category.name }}</option>
                        </select>
                        <span class="text-xs text-ink-muted">{{ $t('admin_checklist.memberi_pengantin_pautan_cari_vendor') }}</span>
                    </label>

                    <UiField
                        v-model="form.months_before"
                        :label="$t('admin_checklist.bulan_sebelum_majlis')"
                        name="months_before"
                        type="number"
                        min="0"
                        max="36"
                        :error="errors.months_before"
                        :help="$t('admin_checklist.bulan_sebelum_majlis_help')"
                    />

                    <UiTextarea v-for="locale in locales" :key="`in-${locale.code}`" v-model="form.notes[locale.code]" :label="`${$t('admin_checklist.nota_2')} · ${locale.label}`" :name="`notes[${locale.code}]`" rows="3" :error="errors[`notes.${locale.code}`]" />
                </template>

                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="is_active" value="0">
                    <input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="accent-brand-600">{{ $t('admin_checklist.aktif') }}</label>

                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">
                    {{ panel.row ? $t('common.simpan') : $t('admin_checklist.tambah') }}
                </button>
            </form>
        </div>
    </div>
</template>
