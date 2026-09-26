<script setup>
/**
 * The vendor's own profile. It posts as an ordinary form so validation,
 * redirects and the image upload keep working exactly as the server expects;
 * Vue is here for the live preview of the card a couple will see.
 */
import { computed, ref } from 'vue';
import { useUploadForm } from '../../composables/useUploadForm.js';
import UiDistrictSelect from '../ui/UiDistrictSelect.vue';
import UiField from '../ui/UiField.vue';
import UiFlagSelect from '../ui/UiFlagSelect.vue';
import UiMultiSelect from '../ui/UiMultiSelect.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    csrf: { type: String, required: true },
    vendor: { type: Object, required: true },
    categories: { type: Array, required: true },
    maxCategories: { type: Number, required: true },
    states: { type: Array, required: true },
    districts: { type: Object, required: true },
    tones: { type: Array, required: true },
    priceUnits: { type: Array, required: true },
    socialPlatforms: { type: Array, required: true },
    errors: { type: Object, default: () => ({}) },
    imageHint: { type: String, required: true },
    logoHint: { type: String, required: true },
});

const { uploading, percent: uploadPercent, error: uploadError, submit: submitUpload } = useUploadForm();
const form = ref({
    ...props.vendor,
    social_links: { ...props.vendor.social_links },
    category_ids: [...props.vendor.category_ids],
    service_states: [...props.vendor.service_states],
});
const coverPreview = ref(props.vendor.cover_image_url);
const logoPreview = ref(props.vendor.logo_url);
const removeLogo = ref(false);

const onCoverChosen = (event) => {
    const file = event.target.files?.[0];
    if (file) coverPreview.value = URL.createObjectURL(file);
};

const onLogoChosen = (event) => {
    const file = event.target.files?.[0];

    if (file) {
        logoPreview.value = URL.createObjectURL(file);
        removeLogo.value = false;
    }
};

const categoryOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: `${c.icon} ${c.name}` })));

/** The primary category and the home state are ticked for good: the card, the
 *  profile heading and the address all print them, so they cannot be dropped. */
const categoryChoices = computed(() =>
    props.categories.map((category) => ({
        value: category.id,
        label: category.name,
        icon: category.icon,
        locked: String(category.id) === String(form.value.category_id),
        note: String(category.id) === String(form.value.category_id) ? 'utama' : null,
    })),
);

const stateChoices = computed(() =>
    props.states.map((option) => ({
        ...option,
        locked: option.value === form.value.state,
        note: option.value === form.value.state ? 'asal' : null,
    })),
);
</script>

<template>
    <form :action="action" method="POST" enctype="multipart/form-data" class="flex flex-col gap-8" @submit="submitUpload">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="PUT">

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">{{ $t('vendor_profile.maklumat_asas') }}</h2>

            <UiField v-model="form.name" :label="$t('vendor_profile.nama_perniagaan')" name="name" :error="errors.name" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <UiSelect v-model="form.category_id" :label="$t('vendor_profile.kategori_utama')" name="category_id" :options="categoryOptions" :error="errors.category_id" required :help="$t('vendor_profile.kategori_ini_yang_dipaparkan_pada')" />
                <UiFlagSelect v-model="form.state" :label="$t('vendor_profile.negeri_asal')" name="state" :options="states" :error="errors.state" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiDistrictSelect v-model="form.district" :state="form.state" :districts="districts" :error="errors.district" />
                <UiField v-model="form.city" :label="$t('vendor_profile.bandar')" name="city" :error="errors.city" required />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiMultiSelect
                    v-model="form.category_ids"
                    :label="$t('vendor_profile.kategori_yang_anda_buat')"
                    name="category_ids[]"
                    :placeholder="$t('vendor_profile.pilih_kategori')"
                    :options="categoryChoices"
                    :max="maxCategories"
                    :error="errors.category_ids || errors['category_ids.0']"
                    :help="$t('vendor_profile.maksimum_kategori', { count: maxCategories })"
                />

                <UiMultiSelect
                    v-model="form.service_states"
                    :label="$t('vendor_profile.negeri_yang_anda_cover')"
                    name="service_states[]"
                    :placeholder="$t('vendor_profile.pilih_negeri')"
                    :options="stateChoices"
                    :error="errors.service_states || errors['service_states.0']"
                    :help="$t('vendor_profile.setiap_negeri_yang_anda_sanggup')"
                />
            </div>
            <UiField v-model="form.tagline" :label="$t('vendor_profile.tagline')" name="tagline" :error="errors.tagline" maxlength="160" :help="$t('vendor_profile.satu_ayat_pendek_pada_kad')" />
            <UiTextarea v-model="form.description" :label="$t('vendor_profile.penerangan')" name="description" :rows="6" :error="errors.description" :help="$t('vendor_profile.ceritakan_perkhidmatan_pengalaman_dan_apa')" />
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">{{ $t('vendor_profile.hubungi_harga') }}</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="form.phone" :label="$t('vendor_profile.telefon')" name="phone" type="tel" :error="errors.phone" />
                <UiField v-model="form.whatsapp" :label="$t('vendor_profile.whatsapp')" name="whatsapp" type="tel" :error="errors.whatsapp" :help="$t('vendor_profile.nombor_dengan_kod_negara_contoh')" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="form.price_from" :label="$t('vendor_profile.harga_bermula_rm')" name="price_from" type="number" step="0.01" min="0" :error="errors.price_from" required :help="$t('vendor_profile.diselaraskan_automatik_dengan_pakej_termurah')" />
                <UiSelect v-model="form.price_unit" :label="$t('vendor_profile.unit_harga')" name="price_unit" :options="priceUnits" :error="errors.price_unit" required />
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">{{ $t('vendor_profile.media_sosial') }}</h2>
                <p class="text-sm text-ink-muted">{{ $t('vendor_profile.dipaparkan_pada_halaman_awam_anda') }}</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField
                    v-for="platform in socialPlatforms"
                    :key="platform.key"
                    v-model="form.social_links[platform.key]"
                    :label="platform.label"
                    :name="`social_links[${platform.key}]`"
                    :placeholder="platform.placeholder"
                    :error="errors[`social_links.${platform.key}`]"
                    maxlength="255"
                />
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">{{ $t('vendor_profile.rupa_kad_vendor') }}</h2>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">{{ $t('vendor_profile.warna_latar') }}</span>
                <div class="flex flex-wrap gap-2">
                    <label v-for="tone in tones" :key="tone" class="cursor-pointer">
                        <input v-model="form.cover_tone" type="radio" name="cover_tone" :value="tone" class="peer sr-only">
                        <span :class="['block size-12 rounded-xl bg-linear-to-br ring-2 ring-transparent ring-offset-2 ring-offset-surface transition peer-checked:ring-brand-600', tone]"></span>
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">{{ $t('vendor_profile.logo_perniagaan') }}</span>
                <div class="flex items-center gap-4">
                    <img v-if="logoPreview && !removeLogo" :src="logoPreview" alt="" class="size-16 shrink-0 rounded-full object-cover">
                    <span v-else :class="['flex size-16 shrink-0 items-center justify-center rounded-full bg-linear-to-br text-xl font-semibold text-white', form.cover_tone]">{{ vendor.initial }}</span>

                    <div class="flex min-w-0 flex-col gap-1">
                        <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="onLogoChosen">
                        <label v-if="vendor.logo_url" class="flex items-center gap-2 text-xs text-ink-muted">
                            <input type="hidden" name="remove_logo" value="0">
                            <input v-model="removeLogo" type="checkbox" name="remove_logo" value="1" class="accent-brand-600">{{ $t('vendor_profile.buang_logo_guna_huruf_nama') }}</label>
                    </div>
                </div>
                <span class="text-xs text-ink-muted">{{ logoHint }}</span>
                <span v-if="errors.logo" class="text-xs text-brand-700">{{ errors.logo }}</span>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">{{ $t('vendor_profile.gambar_muka_depan') }}</span>
                <img v-if="coverPreview" :src="coverPreview" alt="" class="h-40 w-full max-w-xs rounded-xl object-cover">
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="onCoverChosen">
                <span class="text-xs text-ink-muted">{{ imageHint }}</span>
                <span v-if="errors.cover_image" class="text-xs text-brand-700">{{ errors.cover_image }}</span>
                <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" />
            </div>
        </section>

        <div>
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                {{ uploading ? $t('common.memuat_naik') : $t('vendor_profile.simpan_profil') }}
            </button>
        </div>
    </form>
</template>
