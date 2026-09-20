<script setup>
/**
 * The invitation card editor.
 *
 * The itinerary, the contacts and the bank accounts are repeaters rather than a
 * fixed number of blank rows: a couple with three items should not scroll past
 * three empty ones, and a couple with eight should not be told they may have
 * six. The server's own limits are passed in and enforced here too.
 */
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { useUploadForm } from '../../composables/useUploadForm.js';
import UiConfirm from '../ui/UiConfirm.vue';
import UiField from '../ui/UiField.vue';
import UiTextarea from '../ui/UiTextarea.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';

const props = defineProps({
    exists: { type: Boolean, required: true },
    action: { type: String, required: true },
    domain: { type: String, required: true },
    subdomainCheckUrl: { type: String, required: true },
    site: { type: Object, required: true },
    templateGroups: { type: Array, required: true },
    limits: { type: Object, required: true },
    status: { type: Object, default: null },
    gallery: { type: Object, default: null },
    wishes: { type: Array, default: () => [] },
    rsvpSummary: { type: Object, default: null },
    imageHint: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const { uploading, percent: uploadPercent, error: uploadError, submit: submitUpload } = useUploadForm();

const form = ref({ ...props.site });
const coverPreview = ref(props.site.cover_url);
const qrPreview = ref(props.site.gift_qr_url);

/** Repeaters always show one empty row, so there is something to type into. */
const rowsOf = (saved, blank) => ref(saved.length ? saved.map((row) => ({ ...row })) : [{ ...blank }]);

const itinerary = rowsOf(props.site.itinerary, { time: '', label: '' });
const contacts = rowsOf(props.site.contacts, { name: '', phone: '' });
const giftAccounts = rowsOf(props.site.gift_accounts, { bank: '', holder: '', number: '' });

const add = (rows, blank, limit) => {
    if (rows.value.length < limit) rows.value.push({ ...blank });
};

const remove = (rows, at) => {
    rows.value.splice(at, 1);
    if (!rows.value.length) rows.value.push({});
};

/**
 * The address is checked as the couple types, against the same rules the save
 * uses, so "sudah diambil" is known before they fill in everything else.
 */
const savedSubdomain = props.site.subdomain;
const addressCheck = ref({ state: 'idle', message: '', suggestions: [] });
let addressTimer = null;
let addressRequest = null;

/** Lower case, spaces to dashes, nothing a web address cannot hold. */
const tidyAddress = (value) =>
    (value || '')
        .toLowerCase()
        .replace(/[\s_]+/g, '-')
        .replace(/[^a-z0-9-]/g, '')
        .replace(/-{2,}/g, '-')
        .slice(0, 63);

const checkAddress = async (value) => {
    addressRequest?.abort();
    addressRequest = new AbortController();

    try {
        const url = new URL(props.subdomainCheckUrl, window.location.origin);
        url.searchParams.set('subdomain', value);

        const response = await fetch(url, {
            headers: { Accept: 'application/json' },
            signal: addressRequest.signal,
        });
        if (!response.ok) {
            addressCheck.value = { state: 'idle', message: '', suggestions: [] };
            return;
        }
        const result = await response.json();
        addressCheck.value = {
            state: result.available ? 'available' : 'taken',
            message: result.message,
            suggestions: result.suggestions,
        };
    } catch (error) {
        if (error.name !== 'AbortError') addressCheck.value = { state: 'idle', message: '', suggestions: [] };
    }
};

watch(
    () => form.value.subdomain,
    (value) => {
        const tidy = tidyAddress(value);
        if (tidy !== value) {
            form.value.subdomain = tidy;
            return;
        }

        clearTimeout(addressTimer);
        if (props.exists && tidy === savedSubdomain) {
            addressCheck.value = { state: 'idle', message: '', suggestions: [] };
            return;
        }
        if (tidy.length < 3) {
            addressCheck.value = { state: 'short', message: 'Sekurang-kurangnya 3 aksara.', suggestions: [] };
            return;
        }

        addressCheck.value = { ...addressCheck.value, state: 'checking' };
        addressTimer = setTimeout(() => checkAddress(tidy), 350);
    },
    { immediate: !props.exists },
);

onBeforeUnmount(() => {
    clearTimeout(addressTimer);
    addressRequest?.abort();
});

const allTemplates = props.templateGroups.flatMap((group) => group.templates.map((template) => ({ ...template, style: group.style })));

/** Open on the shelf holding the chosen design, not always the first one. */
const activeStyle = ref(allTemplates.find((template) => template.slug === props.site.template)?.style ?? props.templateGroups[0]?.style);
const chosenTemplateName = computed(() => allTemplates.find((template) => template.slug === form.value.template)?.name ?? '—');

const previewFile = (event, target) => {
    const file = event.target.files?.[0];
    if (file) target.value = URL.createObjectURL(file);
};
</script>

<template>
    <div
        v-if="status"
        :class="[
            'mb-6 flex flex-col gap-3 rounded-2xl border p-5 sm:flex-row sm:items-center sm:justify-between',
            status.published ? 'border-emerald-200 bg-emerald-50' : 'border-line bg-surface-raised',
        ]"
    >
        <div class="min-w-0">
            <p class="flex items-center gap-2 text-sm font-semibold">
                <span :class="['size-2 rounded-full', status.published ? 'bg-emerald-500' : 'bg-ink-muted']"></span>
                {{ status.published ? 'Tersiar' : 'Draf, belum tersiar' }}
            </p>

            <template v-if="status.published">
                <a :href="status.url" target="_blank" rel="noopener" class="mt-1 block truncate text-sm text-brand-700 underline underline-offset-4">{{ status.url }}</a>
                <p class="mt-1 text-xs text-ink-muted">
                    {{ status.views }} tontonan · <a :href="status.guests_url" class="hover:text-ink">{{ status.rsvp_count }} tetamu mengesahkan kehadiran</a>
                </p>
            </template>

            <p v-else class="mt-1 text-sm break-words text-ink-muted">{{ status.draft_note }}</p>
        </div>

        <form :action="status.publish_url" method="POST" class="shrink-0">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">
            <input type="hidden" name="published" :value="status.published ? 0 : 1">
            <button
                type="submit"
                :class="[
                    'rounded-full px-5 py-2.5 text-sm font-semibold transition',
                    status.published ? 'border border-line hover:border-brand-400' : 'bg-brand-600 text-white hover:bg-brand-700',
                ]"
            >{{ status.published ? 'Tarik balik' : 'Siarkan kad' }}</button>
        </form>
    </div>

    <form :action="action" method="POST" enctype="multipart/form-data" class="flex flex-col gap-8" @submit="submitUpload">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="PUT">

        <section id="template" class="flex scroll-mt-24 flex-col gap-5 rounded-2xl border border-line bg-surface-raised p-6">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Template</h2>
                    <p class="text-sm text-ink-muted">{{ limits.templates }} reka bentuk dalam {{ templateGroups.length }} gaya. Tekan "Lihat contoh" untuk membuka kad penuh.</p>
                </div>
                <a :href="limits.gallery_url" target="_blank" rel="noopener" class="text-sm font-medium text-brand-600 underline underline-offset-4">Layari galeri</a>
            </div>

            <div class="no-scrollbar -mx-6 flex gap-2 overflow-x-auto px-6" role="tablist" aria-label="Gaya template">
                <button
                    v-for="group in templateGroups"
                    :key="group.style"
                    type="button"
                    role="tab"
                    :aria-selected="activeStyle === group.style"
                    :class="[
                        'shrink-0 rounded-full border px-4 py-1.5 text-sm font-medium transition',
                        activeStyle === group.style ? 'border-brand-600 bg-brand-600 text-white' : 'border-line hover:border-brand-400',
                    ]"
                    @click="activeStyle = group.style"
                >
                    {{ group.style }}
                    <span :class="['ml-1 text-xs', activeStyle === group.style ? 'text-white/70' : 'text-ink-muted']">{{ group.templates.length }}</span>
                </button>
            </div>

            <!-- Every radio stays in the form; only the chosen style's shelf is shown. -->
            <div v-for="group in templateGroups" v-show="activeStyle === group.style" :key="group.style" class="no-scrollbar -mx-6 flex snap-x snap-mandatory gap-3 overflow-x-auto px-6 pb-2">
                <label v-for="template in group.templates" :key="template.slug" class="relative w-32 shrink-0 cursor-pointer snap-start sm:w-36">
                    <input v-model="form.template" type="radio" name="template" :value="template.slug" class="peer sr-only">
                    <span class="flex flex-col gap-2 rounded-xl border border-line bg-surface p-2 transition peer-checked:border-brand-600 peer-checked:ring-2 peer-checked:ring-brand-400/40 hover:border-brand-300">
                        <span class="block overflow-hidden rounded shadow-[0_8px_18px_-10px_rgb(0_0_0/0.4)]" v-html="template.thumbnail"></span>
                        <span class="flex items-center justify-between gap-1 px-0.5">
                            <span class="truncate text-xs font-semibold">{{ template.name }}</span>
                            <svg v-if="form.template === template.slug" class="size-4 shrink-0 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                        </span>
                        <a :href="template.url" target="_blank" rel="noopener" class="px-0.5 text-[11px] text-brand-600 underline underline-offset-2">Lihat contoh</a>
                    </span>
                </label>
            </div>

            <p class="text-sm text-ink-muted">Dipilih: <span class="font-semibold text-ink">{{ chosenTemplateName }}</span> &middot; boleh ditukar bila-bila masa, maklumat anda kekal.</p>
        </section>

        <section id="alamat" class="flex scroll-mt-24 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Alamat web kad</h2>
                <p class="text-sm text-ink-muted">Pilih sendiri pautan yang anda kongsi dengan tetamu. Kami semak sama ada ia masih kosong semasa anda menaip.</p>
            </div>

            <label class="flex flex-col gap-2">
                <span class="sr-only">Alamat web</span>
                <span
                    :class="[
                        'flex items-center overflow-hidden rounded-xl border bg-surface transition',
                        addressCheck.state === 'available' ? 'border-emerald-400' : addressCheck.state === 'taken' || errors.subdomain ? 'border-brand-400' : 'border-line focus-within:border-brand-400',
                    ]"
                >
                    <span class="shrink-0 pl-4 text-sm text-ink-muted">https://</span>
                    <input v-model="form.subdomain" type="text" name="subdomain" required autocomplete="off" autocapitalize="none" spellcheck="false" class="min-w-0 flex-1 bg-transparent px-1 py-2.5 text-base font-medium focus:outline-none sm:text-sm" placeholder="aina-hakim">
                    <span class="shrink-0 border-l border-line bg-surface-muted px-3 py-2.5 text-sm text-ink-muted">.{{ domain }}</span>
                </span>

                <span v-if="errors.subdomain && addressCheck.state === 'idle'" class="text-xs text-brand-700">{{ errors.subdomain }}</span>
                <span v-else-if="addressCheck.state === 'checking'" class="flex items-center gap-1.5 text-xs text-ink-muted">
                    <span class="size-3 animate-spin rounded-full border-2 border-line border-t-brand-500"></span>
                    Menyemak…
                </span>
                <span v-else-if="addressCheck.state === 'available'" class="flex items-center gap-1.5 text-xs font-medium text-emerald-700">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    <span class="break-all">{{ addressCheck.message }}</span>
                </span>
                <span v-else-if="addressCheck.state === 'taken' || addressCheck.state === 'short'" class="flex items-center gap-1.5 text-xs font-medium text-brand-700">
                    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
                    {{ addressCheck.message }}
                </span>
                <span v-else class="text-xs text-ink-muted">Huruf kecil, nombor dan sengkang sahaja. Contoh: aina-hakim</span>
            </label>

            <div v-if="addressCheck.state === 'taken' && addressCheck.suggestions.length" class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-ink-muted">Masih kosong:</span>
                <button
                    v-for="suggestion in addressCheck.suggestions"
                    :key="suggestion"
                    type="button"
                    class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-800 transition hover:border-emerald-400"
                    @click="form.subdomain = suggestion"
                >{{ suggestion }}</button>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Pengantin</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="form.bride_name" label="Nama pengantin perempuan" name="bride_name" :error="errors.bride_name" required />
                <UiField v-model="form.groom_name" label="Nama pengantin lelaki" name="groom_name" :error="errors.groom_name" required />
                <UiField v-model="form.bride_parents" label="Ibu bapa pengantin perempuan" name="bride_parents" placeholder="Zulkifli bin Hassan & Rohana binti Ahmad" :error="errors.bride_parents" />
                <UiField v-model="form.groom_parents" label="Ibu bapa pengantin lelaki" name="groom_parents" placeholder="Ismail bin Yusof & Salmah binti Osman" :error="errors.groom_parents" />
            </div>

            <UiTextarea v-model="form.salutation" label="Kata aluan" name="salutation" :rows="3" :error="errors.salutation" />
            <UiTextarea v-model="form.invitation_note" label="Nota jemputan (pilihan)" name="invitation_note" :rows="3" placeholder="Doa dan restu daripada tuan/puan amat bermakna." :error="errors.invitation_note" />
        </section>

        <section id="majlis" class="flex scroll-mt-24 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Majlis</h2>

            <div class="grid gap-4 sm:grid-cols-3">
                <UiField v-model="form.event_date" label="Tarikh" name="event_date" type="date" :error="errors.event_date" required />
                <UiField v-model="form.starts_at" label="Mula" name="starts_at" type="time" :error="errors.starts_at" />
                <UiField v-model="form.ends_at" label="Tamat" name="ends_at" type="time" :error="errors.ends_at" />
            </div>

            <UiField v-model="form.venue_name" label="Nama tempat" name="venue_name" placeholder="Dewan Seri Melati" :error="errors.venue_name" />
            <UiTextarea v-model="form.venue_address" label="Alamat penuh" name="venue_address" :rows="2" :error="errors.venue_address" />
            <UiField v-model="form.map_url" label="Pautan peta" name="map_url" type="url" placeholder="https://maps.google.com/..." :error="errors.map_url" />

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Gambar utama (pilihan)</span>
                <img v-if="coverPreview" :src="coverPreview" alt="" class="h-32 w-full max-w-xs rounded-xl object-cover">
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="previewFile($event, coverPreview)">
                <span class="text-xs text-ink-muted">{{ imageHint }}</span>
                <span v-if="errors.cover_image" class="text-xs text-brand-700">{{ errors.cover_image }}</span>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Atur cara</h2>
                <p class="text-sm text-ink-muted">Susunan acara pada hari majlis, seperti yang tetamu akan baca.</p>
            </div>

            <div v-for="(row, at) in itinerary" :key="`itinerary-${at}`" class="grid gap-3 sm:grid-cols-[10rem_1fr_auto]">
                <input v-model="row.time" type="text" :name="`itinerary[${at}][time]`" placeholder="11:00 pagi" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <input v-model="row.label" type="text" :name="`itinerary[${at}][label]`" placeholder="Ketibaan tetamu" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <button type="button" class="rounded-full px-3 text-ink-muted transition hover:bg-surface-muted hover:text-ink" aria-label="Buang baris" @click="remove(itinerary, at)">&times;</button>
            </div>

            <button v-if="itinerary.length < limits.itinerary" type="button" class="w-fit rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="add(itinerary, { time: '', label: '' }, limits.itinerary)">+ Tambah baris</button>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Hubungi &amp; RSVP</h2>

            <div v-for="(row, at) in contacts" :key="`contact-${at}`" class="grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                <input v-model="row.name" type="text" :name="`contacts[${at}][name]`" placeholder="Puan Rohana" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <input v-model="row.phone" type="tel" :name="`contacts[${at}][phone]`" placeholder="012-345 6789" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <button type="button" class="rounded-full px-3 text-ink-muted transition hover:bg-surface-muted hover:text-ink" aria-label="Buang baris" @click="remove(contacts, at)">&times;</button>
            </div>

            <button v-if="contacts.length < limits.contacts" type="button" class="w-fit rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="add(contacts, { name: '', phone: '' }, limits.contacts)">+ Tambah nombor</button>

            <label class="flex items-center gap-2 border-t border-line pt-4 text-sm">
                <input type="hidden" name="rsvp_enabled" value="0">
                <input v-model="form.rsvp_enabled" type="checkbox" name="rsvp_enabled" value="1" class="accent-brand-600">
                Benarkan tetamu mengesahkan kehadiran (RSVP)
            </label>

            <UiField v-model="form.rsvp_deadline" class="sm:w-56" label="Tarikh akhir RSVP (pilihan)" name="rsvp_deadline" type="date" :error="errors.rsvp_deadline" />
            <UiTextarea v-model="form.closing_note" label="Nota penutup" name="closing_note" :rows="2" :error="errors.closing_note" />
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Salam kaut</h2>
                <p class="text-sm text-ink-muted">Kod QR DuitNow dan nombor akaun untuk tetamu yang ingin memberi hadiah.</p>
            </div>

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="gift_enabled" value="0">
                <input v-model="form.gift_enabled" type="checkbox" name="gift_enabled" value="1" class="accent-brand-600">
                Papar bahagian hadiah pada kad
            </label>

            <UiTextarea v-model="form.gift_note" label="Nota hadiah (pilihan)" name="gift_note" :rows="2" :error="errors.gift_note" />

            <label class="flex flex-col gap-1.5">
                <span class="text-sm font-medium">Kod QR DuitNow</span>
                <input type="file" name="gift_qr_image" accept="image/jpeg,image/png,image/webp" class="text-sm" @change="previewFile($event, qrPreview)">
                <img v-if="qrPreview" :src="qrPreview" alt="Kod QR DuitNow" class="mt-2 w-32 rounded-xl border border-line">
                <span v-if="errors.gift_qr_image" class="text-xs text-brand-700">{{ errors.gift_qr_image }}</span>
            </label>

            <div v-for="(row, at) in giftAccounts" :key="`gift-${at}`" class="grid gap-3 sm:grid-cols-[1fr_1fr_1fr_auto]">
                <input v-model="row.bank" type="text" :name="`gift_accounts[${at}][bank]`" placeholder="Maybank" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <input v-model="row.holder" type="text" :name="`gift_accounts[${at}][holder]`" placeholder="Nama pemegang akaun" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <input v-model="row.number" type="text" :name="`gift_accounts[${at}][number]`" placeholder="1234 5678 9012" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
                <button type="button" class="rounded-full px-3 text-ink-muted transition hover:bg-surface-muted hover:text-ink" aria-label="Buang baris" @click="remove(giftAccounts, at)">&times;</button>
            </div>

            <button v-if="giftAccounts.length < limits.gift_accounts" type="button" class="w-fit rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400" @click="add(giftAccounts, { bank: '', holder: '', number: '' }, limits.gift_accounts)">+ Tambah akaun</button>

            <label class="flex items-center gap-2 border-t border-line pt-4 text-sm">
                <input type="hidden" name="wishes_enabled" value="0">
                <input v-model="form.wishes_enabled" type="checkbox" name="wishes_enabled" value="1" class="accent-brand-600">
                Papar ucapan tetamu yang anda luluskan pada kad
            </label>
        </section>

        <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" label="Menyimpan kad" />

        <div class="flex flex-wrap gap-3">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                {{ uploading ? 'Menyimpan…' : exists ? 'Simpan kad' : 'Cipta kad jemputan' }}
            </button>
            <a :href="limits.preview_url" target="_blank" rel="noopener" class="rounded-full border border-line px-6 py-3 text-sm font-medium transition hover:border-brand-400">Pratonton</a>
        </div>
    </form>

    <section v-if="gallery" class="mt-10 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">Galeri gambar</h2>

        <form :action="gallery.store_url" method="POST" enctype="multipart/form-data" class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5" @submit="submitUpload">
            <input type="hidden" name="_token" :value="csrf">
            <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required class="text-sm">
            <input type="text" name="caption" placeholder="Kapsyen (pilihan)" class="rounded-xl border border-line bg-surface px-4 py-2.5 text-sm focus:border-brand-400 focus:outline-none">
            <span class="text-xs text-ink-muted">{{ imageHint }}</span>

            <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" />

            <button type="submit" class="w-fit rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                {{ uploading ? 'Memuat naik…' : 'Muat naik gambar' }}
            </button>
        </form>

        <ul v-if="gallery.photos.length" class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            <li v-for="photo in gallery.photos" :key="photo.url" class="flex flex-col gap-1.5">
                <img :src="photo.url" :alt="photo.caption || ''" loading="lazy" class="h-32 w-full rounded-xl object-cover">
                <UiConfirm
                    :action="photo.destroy_url"
                    method="DELETE"
                    tone="danger"
                    title="Padam gambar ini?"
                    message="Gambar akan dibuang dari galeri kad jemputan."
                    confirm-label="Padam"
                    trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                    :csrf="csrf"
                >Padam</UiConfirm>
            </li>
        </ul>
    </section>

    <section v-if="wishes.length" class="mt-10 flex flex-col gap-4">
        <h2 class="font-display text-xl font-semibold">Ucapan tetamu</h2>
        <p class="text-sm text-ink-muted">Hanya ucapan yang anda luluskan akan dipaparkan pada kad jemputan.</p>

        <ul class="flex flex-col gap-3">
            <li v-for="wish in wishes" :key="wish.id" class="flex flex-col gap-2 rounded-2xl border border-line bg-surface-raised p-4 sm:flex-row sm:items-center">
                <div class="min-w-0 flex-1">
                    <p class="text-sm text-ink-muted italic">“{{ wish.message }}”</p>
                    <p class="mt-1 text-sm font-medium">{{ wish.name }}</p>
                </div>

                <form :action="wish.update_url" method="POST" class="shrink-0">
                    <input type="hidden" name="_token" :value="csrf">
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="approve_message" :value="wish.public ? 0 : 1">
                    <button
                        type="submit"
                        :class="[
                            'rounded-full px-5 py-2 text-xs font-semibold transition',
                            wish.public ? 'border border-line hover:border-brand-400' : 'bg-brand-600 text-white hover:bg-brand-700',
                        ]"
                    >{{ wish.public ? 'Sembunyikan' : 'Luluskan' }}</button>
                </form>
            </li>
        </ul>
    </section>

    <section v-if="rsvpSummary" class="mt-10 flex flex-col items-start gap-3 rounded-2xl border border-line bg-surface-raised p-5">
        <h2 class="font-display text-xl font-semibold">{{ rsvpSummary.count }} jawapan RSVP diterima</h2>
        <p class="text-sm text-ink-muted">Lihat siapa yang menjawab, siapa yang belum, dan jumlah kehadiran yang disahkan di halaman tetamu.</p>
        <a :href="rsvpSummary.url" class="rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Buka senarai tetamu</a>
    </section>
</template>
