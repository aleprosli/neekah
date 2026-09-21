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
                :label="$t('admin_post.tajuk')"
                name="title"
                maxlength="160"
                :placeholder="$t('admin_post.contoh_10_tips_memilih_pelamin')"
                :error="errors.title"
                required
            />

            <UiRichEditor
                v-model="form.body"
                name="body"
                :label="$t('admin_post.isi_artikel')"
                :help="$t('admin_post.guna_h2_untuk_setiap_bahagian')"
                :upload-url="imageUploadUrl"
                :csrf="csrf"
                :error="errors.body"
            />

            <UiTextarea
                v-model="form.excerpt"
                :label="$t('admin_post.ringkasan')"
                name="excerpt"
                :rows="3"
                maxlength="300"
                :help="$t('admin_post.dipaparkan_dalam_senarai_blog_jika')"
                :error="errors.excerpt"
            />
        </div>

        <aside class="flex flex-col gap-5">
            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">{{ $t('admin_post.siaran') }}</h2>

                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.status" type="radio" name="status" value="draft" class="accent-brand-600">{{ $t('admin_post.draf') }}</label>
                <label class="flex items-center gap-2 text-sm">
                    <input v-model="form.status" type="radio" name="status" value="published" class="accent-brand-600">{{ $t('admin_post.siarkan') }}</label>

                <UiField
                    v-model="form.published_at"
                    :label="$t('admin_post.tarikh_siaran')"
                    name="published_at"
                    type="datetime-local"
                    :help="$t('admin_post.waktu_malaysia_kosongkan_untuk_siar')"
                    :error="errors.published_at"
                />

                <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700 disabled:opacity-50" :disabled="uploading">
                    {{ uploading ? 'Memuat naik…' : 'Simpan' }}
                </button>

                <UiUploadProgress :uploading="uploading" :percent="uploadPercent" :error="uploadError" />
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">{{ $t('admin_post.gambar_utama') }}</h2>

                <img v-if="coverPreview" :src="coverPreview" alt="" class="aspect-[16/10] w-full rounded-xl object-cover">

                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="text-sm file:mr-3 file:rounded-full file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-700" @change="onCoverChosen">

                <p class="text-xs text-ink-muted">{{ $t('admin_post.juga_menjadi_gambar_pratonton_apabila') }}</p>
                <span v-if="errors.cover_image" class="text-xs text-brand-700">{{ errors.cover_image }}</span>
            </section>

            <section class="flex flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
                <h2 class="font-semibold">SEO</h2>

                <UiField v-model="form.slug" :label="$t('admin_post.slug_url')" name="slug" maxlength="180" help="neekah.my/blog/slug-anda. Kosongkan untuk jana daripada tajuk." :error="errors.slug" />
                <UiField v-model="form.meta_title" :label="$t('admin_post.tajuk_seo')" name="meta_title" maxlength="70" :help="$t('admin_post.tajuk_dalam_hasil_carian_google')" :error="errors.meta_title" />
                <UiTextarea v-model="form.meta_description" :label="$t('admin_post.deskripsi_seo')" name="meta_description" :rows="3" maxlength="170" :help="$t('admin_post.ayat_di_bawah_tajuk_dalam')" :error="errors.meta_description" />
            </section>
        </aside>
    </form>
</template>
