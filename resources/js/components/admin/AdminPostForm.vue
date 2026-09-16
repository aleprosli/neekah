<script setup>
/** Writing an article: the body, when it goes live, and how Google will read it. */
import { ref } from 'vue';
import { useUploadForm } from '../../composables/useUploadForm.js';
import UiField from '../ui/UiField.vue';
import UiRichEditor from '../ui/UiRichEditor.vue';
import UiTextarea from '../ui/UiTextarea.vue';
import UiUploadProgress from '../ui/UiUploadProgress.vue';

const props = defineProps({
    action: { type: String, required: true },
    editing: { type: Boolean, default: false },
    imageUploadUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    post: { type: Object, required: true },
    errors: { type: Object, default: () => ({}) },
});

const { uploading, percent: uploadPercent, error: uploadError, submit: submitUpload } = useUploadForm();

const form = ref({ ...props.post });
const coverPreview = ref(props.post.cover_url);

const onCoverChosen = (event) => {
    const file = event.target.files?.[0];
    if (file) coverPreview.value = URL.createObjectURL(file);
};
</script>

<template>
    <form :action="action" method="POST" enctype="multipart/form-data" class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_320px]" @submit="submitUpload">
        <input type="hidden" name="_token" :value="csrf">
        <input v-if="editing" type="hidden" name="_method" value="PUT">

        <div class="flex min-w-0 flex-col gap-5">
            <UiField
                v-model="form.title"
                label="Tajuk"
                name="title"
                maxlength="160"
                placeholder="Contoh: 10 Tips Memilih Pelamin untuk Majlis Kecil"
                :error="errors.title"
                required
            />

            <UiRichEditor
                v-model="form.body"
                name="body"
                label="Isi artikel"
                help="Guna H2 untuk setiap bahagian utama dan H3 di bawahnya. Google membaca struktur ini."
                :upload-url="imageUploadUrl"
                :csrf="csrf"
                :error="errors.body"
            />

            <UiTextarea
                v-model="form.excerpt"
                label="Ringkasan"
                name="excerpt"
                :rows="3"
                maxlength="300"
                help="Dipaparkan dalam senarai blog. Jika kosong, pembukaan artikel digunakan."
                :error="errors.excerpt"
            />
        </div>

        <aside class="flex flex-col gap-5">
            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">Siaran</h2>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.status" type="radio" name="status" value="draft" class="accent-brand-600">
                    Draf
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.status" type="radio" name="status" value="published" class="accent-brand-600">
                    Siarkan
                </label>

                <UiField
                    v-model="form.published_at"
                    label="Tarikh siaran"
                    name="published_at"
                    type="datetime-local"
                    help="Waktu Malaysia. Kosongkan untuk siar sekarang; tarikh akan datang menjadikannya dijadualkan."
                    :error="errors.published_at"
                />

                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                    {{ uploading ? 'Memuat naik…' : 'Simpan' }}
                </button>

                <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" />
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">Gambar utama</h2>

                <img v-if="coverPreview" :src="coverPreview" alt="" class="aspect-[16/10] w-full rounded-xl object-cover">

                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="onCoverChosen">

                <p class="text-xs text-ink-muted">Juga menjadi gambar pratonton apabila pautan dikongsi di WhatsApp dan Facebook. Nisbah 16:9 paling sesuai.</p>
                <span v-if="errors.cover_image" class="text-xs text-brand-700">{{ errors.cover_image }}</span>
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">SEO</h2>

                <UiField v-model="form.slug" label="Slug URL" name="slug" maxlength="180" help="neekah.my/blog/slug-anda. Kosongkan untuk jana daripada tajuk." :error="errors.slug" />
                <UiField v-model="form.meta_title" label="Tajuk SEO" name="meta_title" maxlength="70" help="Tajuk dalam hasil carian Google, sekitar 60 aksara. Kosongkan untuk guna tajuk artikel." :error="errors.meta_title" />
                <UiTextarea v-model="form.meta_description" label="Deskripsi SEO" name="meta_description" :rows="3" maxlength="170" help="Ayat di bawah tajuk dalam Google, sekitar 160 aksara. Masukkan kata kunci utama." :error="errors.meta_description" />
            </section>
        </aside>
    </form>
</template>
