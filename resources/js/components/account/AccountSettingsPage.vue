<script setup>
/**
 * The one page every role edits their own account on: the person behind the
 * account, not the business. A vendor's name, logo and business details stay
 * in the vendor area.
 */
import UiField from '../ui/UiField.vue';

defineProps({
    user: { type: Object, required: true },
    updateUrl: { type: String, required: true },
    passwordUrl: { type: String, required: true },
    profileUrl: { type: String, default: null },
    csrf: { type: String, required: true },
    errors: { type: Object, default: () => ({}) },
});

const card = 'flex min-w-0 flex-col gap-4 rounded-2xl border border-line bg-surface-raised p-6';
const button = 'self-start rounded-full bg-brand-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-700';
</script>

<template>
    <div class="grid gap-6 break-words lg:grid-cols-2">
        <form method="POST" :action="updateUrl" :class="card">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">

            <h2 class="font-semibold">Maklumat peribadi</h2>

            <p v-if="profileUrl" class="text-sm text-ink-muted">
                Ini maklumat anda sebagai pemilik. Nama, logo dan maklumat perniagaan diuruskan di
                <a :href="profileUrl" class="font-medium text-brand-600 underline underline-offset-4">Profil</a>.
            </p>

            <UiField :model-value="user.name" label="Nama penuh" name="name" autocomplete="name" :error="errors.name" required />
            <UiField :model-value="user.phone" label="Nombor telefon" name="phone" type="tel" autocomplete="tel" placeholder="012-345 6789" :error="errors.phone" />
            <UiField
                :model-value="user.email"
                label="Emel"
                name="email"
                type="email"
                autocomplete="email"
                :help="user.has_password ? 'Menukar emel memerlukan kata laluan semasa anda.' : null"
                :error="errors.email"
                required
            />

            <UiField
                v-if="user.has_password"
                label="Kata laluan semasa"
                name="current_password"
                type="password"
                autocomplete="current-password"
                help="Isi hanya jika anda menukar emel."
                :error="errors.current_password"
            />

            <button type="submit" :class="button">Simpan maklumat</button>
        </form>

        <form method="POST" :action="passwordUrl" :class="card">
            <input type="hidden" name="_token" :value="csrf">
            <input type="hidden" name="_method" value="PUT">

            <h2 class="font-semibold">{{ user.has_password ? 'Tukar kata laluan' : 'Tetapkan kata laluan' }}</h2>

            <template v-if="user.has_password">
                <p class="text-sm text-ink-muted">Selepas ditukar, anda akan dilog keluar daripada peranti lain.</p>
                <UiField label="Kata laluan semasa" name="current_password" type="password" autocomplete="current-password" :error="errors.current_password" required />
            </template>

            <p v-else class="text-sm text-ink-muted">
                Anda log masuk dengan Google, jadi akaun ini belum ada kata laluan. Tetapkan satu supaya anda boleh log masuk tanpa Google.
            </p>

            <UiField label="Kata laluan baru" name="password" type="password" autocomplete="new-password" :error="errors.password" required />
            <UiField label="Sahkan kata laluan baru" name="password_confirmation" type="password" autocomplete="new-password" required />

            <button type="submit" :class="button">{{ user.has_password ? 'Tukar kata laluan' : 'Tetapkan kata laluan' }}</button>
        </form>
    </div>
</template>
