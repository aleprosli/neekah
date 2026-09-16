<script setup>
/**
 * Adding or editing a package. The contents list is the heart of it: one input
 * per item, reorderable, because a package is mostly its list.
 */
import { computed, nextTick, ref } from 'vue';
import UiField from '../ui/UiField.vue';
import UiTextarea from '../ui/UiTextarea.vue';

const props = defineProps({
    action: { type: String, required: true },
    cancelUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    editing: { type: Boolean, default: false },
    packageData: { type: Object, required: true },
    imageHint: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const form = ref({ ...props.packageData });
const features = ref(props.packageData.features.length ? [...props.packageData.features] : ['']);
const imagePreview = ref(props.packageData.image_url);
const removeImage = ref(false);
const inputs = ref([]);
const dragging = ref(null);

const visibleCount = computed(() => features.value.filter((feature) => feature.trim() !== '').length);

const addFeature = async (after = null) => {
    const at = after === null ? features.value.length : after + 1;
    features.value.splice(at, 0, '');
    await nextTick();
    inputs.value[at]?.focus();
};

const removeFeature = (at) => {
    if (features.value.length > 1) features.value.splice(at, 1);
};

const move = (from, to) => {
    if (to < 0 || to >= features.value.length || from === to) return;
    features.value.splice(to, 0, ...features.value.splice(from, 1));
};

const onDrop = (to) => {
    if (dragging.value !== null) move(dragging.value, to);
    dragging.value = null;
};

const onImageChosen = (event) => {
    const file = event.target.files?.[0];
    if (file) {
        imagePreview.value = URL.createObjectURL(file);
        removeImage.value = false;
    }
};
</script>

<template>
    <form :action="action" method="POST" enctype="multipart/form-data" class="flex max-w-3xl flex-col gap-6">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="editing" type="hidden" name="_method" value="PUT">

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div>
                <h2 class="font-semibold">Maklumat asas</h2>
                <p class="text-sm text-ink-muted">Nama, harga dan tempoh yang dipaparkan pada profil anda.</p>
            </div>

            <UiField v-model="form.name" label="Nama pakej" name="name" placeholder="Cth: Pakej Premium" :error="errors.name" required />

            <div class="grid gap-4 sm:grid-cols-2">
                <UiField v-model="form.price" label="Harga (RM)" name="price" type="number" step="0.01" min="0" :error="errors.price" required />
                <UiField v-model="form.duration" label="Tempoh" name="duration" placeholder="10 jam / 1 hari / per pax" :error="errors.duration" />
            </div>

            <UiTextarea v-model="form.description" label="Penerangan ringkas" name="description" :rows="3" placeholder="Satu atau dua ayat tentang pakej ini." :error="errors.description" />

            <div class="flex flex-col gap-2">
                <span class="text-sm font-medium">Gambar pakej</span>
                <p class="text-xs text-ink-muted">Dipaparkan pada kad pakej di halaman awam anda.</p>

                <div v-if="imagePreview" class="flex flex-wrap items-center gap-4">
                    <img :src="imagePreview" alt="" :class="['h-28 w-44 rounded-xl border border-line object-cover', removeImage ? 'opacity-30' : '']">
                    <label v-if="packageData.image_url" class="flex items-center gap-2 text-sm">
                        <input v-model="removeImage" type="checkbox" name="remove_image" value="1" class="accent-brand-600">
                        Buang gambar ini
                    </label>
                </div>

                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="onImageChosen">
                <span class="text-xs text-ink-muted">{{ imageHint }}</span>
                <span v-if="errors.image" class="text-xs text-brand-700">{{ errors.image }}</span>
            </div>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-semibold">Kandungan pakej</h2>
                    <p class="text-sm text-ink-muted">Satu item satu baris. Seret <span aria-hidden="true">⠿</span> untuk susun semula.</p>
                </div>
                <span class="rounded-full bg-surface-muted px-3 py-1 text-xs font-medium text-ink-muted">{{ visibleCount }} item</span>
            </div>

            <ul class="flex flex-col gap-2">
                <li
                    v-for="(feature, at) in features"
                    :key="at"
                    draggable="true"
                    :class="[
                        'flex items-center gap-2 rounded-xl border border-line bg-surface px-2 py-1.5 transition focus-within:border-brand-400 focus-within:ring-2 focus-within:ring-brand-400/40',
                        dragging === at ? 'opacity-40' : '',
                    ]"
                    @dragstart="dragging = at"
                    @dragover.prevent
                    @drop="onDrop(at)"
                    @dragend="dragging = null"
                >
                    <span class="cursor-grab px-1 text-ink-muted select-none" aria-hidden="true">⠿</span>
                    <span class="text-brand-600" aria-hidden="true">✓</span>
                    <input
                        ref="inputs"
                        v-model="features[at]"
                        type="text"
                        name="features[]"
                        maxlength="200"
                        aria-label="Item pakej"
                        placeholder="Cth: 2 jurugambar sepanjang majlis"
                        class="min-w-0 flex-1 bg-transparent py-1.5 text-sm focus:outline-none"
                        @keydown.enter.prevent="addFeature(at)"
                    >
                    <button
                        type="button"
                        class="rounded-full px-2 py-1 text-ink-muted transition hover:bg-surface-muted hover:text-ink disabled:opacity-30"
                        :disabled="features.length === 1"
                        aria-label="Buang item"
                        @click="removeFeature(at)"
                    >&times;</button>
                </li>
            </ul>

            <div class="flex flex-wrap items-center gap-3">
                <button type="button" class="rounded-full border border-line px-4 py-2 text-sm font-medium transition hover:border-brand-400 hover:text-brand-700" @click="addFeature()">+ Tambah item</button>
                <span class="text-xs text-ink-muted">Tekan Enter untuk terus tambah baris seterusnya.</span>
            </div>

            <span v-if="errors.features" class="text-xs text-brand-700">{{ errors.features }}</span>
        </section>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6">
            <label class="flex items-start gap-3">
                <input type="hidden" name="is_active" value="0">
                <input v-model="form.is_active" type="checkbox" name="is_active" value="1" class="mt-1 accent-brand-600">
                <span>
                    <span class="text-sm font-medium">Aktif dan boleh ditempah</span>
                    <span class="block text-xs text-ink-muted">Pakej tidak aktif disembunyikan daripada pengantin.</span>
                </span>
            </label>
        </section>

        <div class="flex flex-wrap gap-2">
            <button type="submit" class="rounded-full bg-brand-600 px-8 py-3 text-sm font-semibold text-white transition hover:bg-brand-700">{{ editing ? 'Simpan' : 'Tambah pakej' }}</button>
            <a :href="cancelUrl" class="rounded-full px-6 py-3 text-sm font-medium text-ink-muted transition hover:bg-surface-muted">Batal</a>
        </div>
    </form>
</template>
