<script setup>
/**
 * The vendor's own profile. It posts as an ordinary form so validation,
 * redirects and the image upload keep working exactly as the server expects;
 * Vue is here for the live preview of the card a couple will see.
 */
import { computed, ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiSelect from '../ui/UiSelect.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    csrf: { type: String, required: true },
    vendor: { type: Object, required: true },
    categories: { type: Array, required: true },
    states: { type: Array, required: true },
    tones: { type: Array, required: true },
    priceUnits: { type: Array, required: true },
    errors: { type: Object, default: () => ({}) },
    imageHint: { type: String, required: true },
});

const form = ref({ ...props.vendor });
const coverPreview = ref(props.vendor.cover_image_url);

const onCoverChosen = (event) => {
    const file = event.target.files?.[0];
    if (file) coverPreview.value = URL.createObjectURL(file);
};

const categoryOptions = computed(() => props.categories.map((c) => ({ value: c.id, label: `${c.icon} ${c.name}` })));
const stateOptions = computed(() => props.states.map((state) => ({ value: state, label: state })));
</script>

<template>
    <form :action="action" method="POST" enctype="multipart/form-data" class="flex flex-col gap-8">
        <input type="hidden" name="_token" :value="csrf">
        <input type="hidden" name="_method" value="PUT">

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Maklumat asas</h2>

            <UiField v-model="form.name" label="Nama perniagaan" name="name" :error="errors.name" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <UiSelect v-model="form.category_id" label="Kategori" name="category_id" :options="categoryOptions" :error="errors.category_id" required />
                <UiSelect v-model="form.state" label="Negeri" name="state" :options="stateOptions" :error="errors.state" required />
            </div>

            <UiField v-model="form.city" label="Bandar" name="city" :error="errors.city" required />
            <UiField v-model="form.tagline" label="Tagline" name="tagline" :error="errors.tagline" maxlength="160" help="Satu ayat pendek pada kad vendor, maksimum 160 aksara." />
            <UiTextarea v-model="form.description" label="Penerangan" name="description" :rows="6" :error="errors.description" help="Ceritakan perkhidmatan, pengalaman dan apa yang membezakan anda." />
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Hubungi &amp; harga</h2>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="form.phone" label="Telefon" name="phone" type="tel" :error="errors.phone" />
                <UiField v-model="form.whatsapp" label="WhatsApp" name="whatsapp" type="tel" :error="errors.whatsapp" help="Nombor dengan kod negara, contoh 60123456789." />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="form.price_from" label="Harga bermula (RM)" name="price_from" type="number" step="0.01" min="0" :error="errors.price_from" required help="Diselaraskan automatik dengan pakej termurah bila anda tambah pakej." />
                <UiSelect v-model="form.price_unit" label="Unit harga" name="price_unit" :options="priceUnits" :error="errors.price_unit" required />
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <h2 class="font-semibold">Rupa kad vendor</h2>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Warna latar</span>
                <div class="flex flex-wrap gap-2">
                    <label v-for="tone in tones" :key="tone" class="cursor-pointer">
                        <input v-model="form.cover_tone" type="radio" name="cover_tone" :value="tone" class="peer sr-only">
                        <span :class="['block size-12 rounded-xl bg-linear-to-br ring-2 ring-transparent ring-offset-2 ring-offset-surface transition peer-checked:ring-brand-600', tone]"></span>
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Gambar muka depan</span>
                <img v-if="coverPreview" :src="coverPreview" alt="" class="h-40 w-full max-w-xs rounded-xl object-cover">
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="onCoverChosen">
                <span class="text-xs text-ink-muted">{{ imageHint }}</span>
                <span v-if="errors.cover_image" class="text-xs text-brand-700">{{ errors.cover_image }}</span>
            </div>
        </section>

        <div>
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">Simpan profil</button>
        </div>
    </form>
</template>
