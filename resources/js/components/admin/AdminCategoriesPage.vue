<script setup>
/**
 * Categories drive the marketplace filters, so they are edited in place: each
 * row is its own form, and a category no vendor uses can be deleted.
 */
import UiConfirm from '../ui/UiConfirm.vue';
import UiField from '../ui/UiField.vue';

defineProps({
    categories: { type: Array, required: true },
    storeUrl: { type: String, required: true },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});
</script>

<template>
    <div class="grid gap-8 lg:grid-cols-[1fr_320px]">
        <div class="min-w-0 overflow-x-auto rounded-2xl border border-line">
            <table class="w-full min-w-[720px] text-sm">
                <thead class="bg-surface-muted text-left text-xs tracking-wide text-ink-muted uppercase">
                    <tr>
                        <th class="px-4 py-3 font-semibold">Kategori</th>
                        <th class="px-4 py-3 text-right font-semibold">Vendor</th>
                        <th class="px-4 py-3 font-semibold">Status</th>
                        <th class="px-4 py-3"><span class="sr-only">Tindakan</span></th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-line">
                    <tr v-for="category in categories" :key="category.id">
                        <td class="px-4 py-3">
                            <form :action="category.update_url" method="POST" class="flex flex-wrap items-center gap-2">
                                <input type="hidden" name="_token" :value="csrf">
                                <input type="hidden" name="_method" value="PUT">

                                <img v-if="category.illustration" :src="category.illustration" alt="" class="size-9 shrink-0 object-contain mix-blend-multiply" title="Ilustrasi kategori">
                                <span v-else class="flex size-9 shrink-0 items-center justify-center text-xl">{{ category.icon }}</span>

                                <input type="text" name="icon" :value="category.icon" class="w-14 rounded-lg border border-line bg-surface px-2 py-1.5 text-center" aria-label="Ikon">
                                <input type="text" name="name" :value="category.name" class="w-32 rounded-lg border border-line bg-surface px-3 py-1.5 sm:w-40" aria-label="Nama">
                                <input type="text" name="examples" :value="category.examples" class="w-40 rounded-lg border border-line bg-surface px-3 py-1.5 text-xs sm:w-56" aria-label="Contoh">
                                <input type="number" name="sort_order" :value="category.sort_order" class="w-16 rounded-lg border border-line bg-surface px-2 py-1.5" aria-label="Susunan">

                                <label class="flex items-center gap-1 text-xs">
                                    <input type="hidden" name="is_active" value="0">
                                    <input type="checkbox" name="is_active" value="1" class="accent-brand-600" :checked="category.is_active">
                                    Aktif
                                </label>

                                <button type="submit" class="rounded-full border border-line px-3 py-1.5 text-xs font-medium transition hover:border-brand-400">Simpan</button>
                            </form>
                        </td>

                        <td class="px-4 py-3 text-right">{{ category.vendors_count }}</td>

                        <td class="px-4 py-3">
                            <span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', category.is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-surface-muted text-ink-muted']">
                                {{ category.is_active ? 'Aktif' : 'Tidak aktif' }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-right">
                            <UiConfirm
                                v-if="category.vendors_count === 0"
                                :action="category.destroy_url"
                                method="DELETE"
                                tone="danger"
                                :title="`Padam kategori ${category.name}?`"
                                message="Kategori ini tiada vendor, jadi ia selamat dipadam. Tindakan ini tidak boleh dibatalkan."
                                confirm-label="Padam kategori"
                                trigger-class="text-xs font-medium text-ink-muted hover:text-brand-700"
                                :csrf="csrf"
                            >Padam</UiConfirm>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <form :action="storeUrl" method="POST" class="flex h-fit flex-col gap-3 rounded-2xl border border-line bg-surface-raised p-5">
            <input type="hidden" name="_token" :value="csrf">
            <h2 class="font-semibold">Tambah kategori</h2>

            <UiField label="Nama" name="name" placeholder="Kereta Pengantin" :error="errors.name" required />
            <UiField label="Ikon (emoji)" name="icon" placeholder="🚗" :error="errors.icon" help="Digunakan jika kategori ini belum ada ilustrasi SVG." required />
            <UiField label="Contoh" name="examples" placeholder="Sewa kereta, deco kereta" :error="errors.examples" />
            <UiField label="Susunan" name="sort_order" type="number" model-value="99" :error="errors.sort_order" />

            <label class="flex items-center gap-2 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="accent-brand-600" checked>
                Aktif
            </label>

            <button type="submit" class="rounded-full bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700">Tambah</button>
        </form>
    </div>
</template>
